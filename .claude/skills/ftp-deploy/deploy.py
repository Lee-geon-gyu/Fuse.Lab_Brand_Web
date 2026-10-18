"""카페24 FTP 배포. 사용법은 같은 폴더의 SKILL.md

python .claude/skills/ftp-deploy/deploy.py [--all] [--dry-run] [--schema] [--allow-dirty]
"""
import argparse
import ftplib
import io
import os
import posixpath
import secrets
import subprocess
import sys
import urllib.request

ROOT = subprocess.run(["git", "rev-parse", "--show-toplevel"], capture_output=True, text=True, check=True).stdout.strip()
os.chdir(ROOT)

# AGENTS.md 배포 업로드 제외 + 에이전트 설정
EXCLUDE_PREFIX = ("docker/", "docker-compose", "setup.", ".env", ".git", ".claude/", "code ")
EXCLUDE_SUFFIX = (".md",)
# 서버에만 있는 파일. 지우지도 덮어쓰지도 않음
PROTECTED = ("app/config.local.php", "app/storage/", "uploads/posts/")
REV_FILE = ".deploy-rev"  # 원격 웹 루트에 마지막 배포 커밋 기록 (루트 .htaccess 가 ^\. 차단)


def git(*args):
    return subprocess.run(["git", *args], capture_output=True, text=True, check=True).stdout


def load_env():
    env = {}
    with open(".env", encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if line and not line.startswith("#") and "=" in line:
                k, v = line.split("=", 1)
                env[k.strip()] = v.strip()
    for k in ("FTP_HOST", "FTP_USER", "FTP_PASSWORD", "FTP_REMOTE_DIR"):
        if not env.get(k):
            sys.exit(f".env 에 {k} 가 없음")
    return env


def deployable(path):
    return (
        path
        and not path.startswith(EXCLUDE_PREFIX)
        and not path.endswith(EXCLUDE_SUFFIX)
        and not path.startswith(PROTECTED)
    )


def connect(env):
    ftp = ftplib.FTP()
    ftp.connect(env["FTP_HOST"], int(env.get("FTP_PORT") or 21), timeout=60)
    ftp.login(env["FTP_USER"], env["FTP_PASSWORD"])
    ftp.encoding = "utf-8"
    return ftp


def remote_rev(ftp, base):
    buf = io.BytesIO()
    try:
        ftp.retrbinary(f"RETR {base}/{REV_FILE}", buf.write)
    except ftplib.error_perm:
        return None
    return buf.getvalue().decode().strip() or None


def ensure_dirs(ftp, base, rel_dir, made):
    cur = base
    for part in rel_dir.split("/"):
        cur = f"{cur}/{part}"
        if cur in made:
            continue
        try:
            ftp.mkd(cur)
        except ftplib.error_perm:
            pass  # 이미 있음
        made.add(cur)


def run_schema(ftp, base, site):
    """일회용 PHP 로 db/schema.sql 적용 후 즉시 삭제"""
    name = f"_schema-{secrets.token_hex(12)}.php"
    php = r'''<?php
header('Content-Type: text/plain; charset=utf-8');
$c = (require __DIR__ . '/app/config.php')['db'];
$pdo = new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4", $c['user'], $c['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$sql = preg_replace('/^\s*--.*$/m', '', (string) file_get_contents(__DIR__ . '/db/schema.sql'));
$n = 0;
foreach (preg_split('/;\s*$/m', $sql) as $stmt) {
    if (trim($stmt) === '') continue;
    $pdo->exec($stmt);
    $n++;
}
echo "schema.sql: {$n} statements\nDONE\n";
'''
    ftp.storbinary(f"STOR {base}/{name}", io.BytesIO(php.encode()))
    try:
        with urllib.request.urlopen(f"{site}/{name}", timeout=60) as r:
            print(r.read().decode("utf-8", "replace"))
    finally:
        ftp.delete(f"{base}/{name}")
        print(f"삭제: {name}")


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--all", action="store_true", help="변경분이 아니라 배포 대상 전체 업로드")
    ap.add_argument("--dry-run", action="store_true", help="목록만 출력")
    ap.add_argument("--schema", action="store_true", help="업로드 후 db/schema.sql 적용")
    ap.add_argument("--allow-dirty", action="store_true", help="커밋 안 된 변경이 있어도 진행")
    args = ap.parse_args()

    env = load_env()
    base = "/" + env["FTP_REMOTE_DIR"].strip("/")  # 로그인 홈이 /www 라 반드시 절대 경로
    site = env.get("DEPLOY_URL") or f"https://{env['FTP_HOST']}"

    dirty = [l for l in git("status", "--porcelain", "--untracked-files=no").splitlines() if deployable(l[3:])]
    if dirty and not args.allow_dirty:
        sys.exit("커밋 안 된 배포 대상 변경이 있음 (--allow-dirty 로 무시):\n" + "\n".join(dirty))

    head = git("rev-parse", "HEAD").strip()
    ftp = connect(env)
    prev = None if args.all else remote_rev(ftp, base)

    if prev:
        try:
            git("cat-file", "-e", prev + "^{commit}")
        except subprocess.CalledProcessError:
            sys.exit(f"원격 {REV_FILE} 의 커밋 {prev} 가 로컬에 없음. git fetch 후 다시 하거나 --all")
        upload, delete = [], []
        for line in git("diff", "--name-status", "--no-renames", prev, head).splitlines():
            status, path = line.split("\t", 1)
            if not deployable(path):
                continue
            (delete if status == "D" else upload).append(path)
        mode = f"변경분 {prev[:7]}..{head[:7]}"
    else:
        upload = [p for p in git("ls-files").splitlines() if deployable(p)]
        delete = []
        mode = "전체" if args.all else f"전체 (원격 {REV_FILE} 없음)"

    print(f"[{mode}] 대상 {base} / 업로드 {len(upload)} / 삭제 {len(delete)}")
    for p in upload:
        print("  +", p)
    for p in delete:
        print("  -", p)
    if args.dry_run:
        ftp.quit()
        return

    made = set()
    for p in upload:
        d = posixpath.dirname(p)
        if d:
            ensure_dirs(ftp, base, d, made)
        with open(p, "rb") as fh:
            ftp.storbinary(f"STOR {base}/{p}", fh)
    for p in delete:
        try:
            ftp.delete(f"{base}/{p}")
        except ftplib.error_perm as e:
            print(f"  삭제 실패(무시): {p} {e}")
    for d in ("uploads/posts", "app/storage"):
        ensure_dirs(ftp, base, d, made)
    ftp.storbinary(f"STOR {base}/{REV_FILE}", io.BytesIO(head.encode()))
    print(f"배포 완료: {head[:7]}")

    if args.schema:
        run_schema(ftp, base, site)
    ftp.quit()


if __name__ == "__main__":
    main()
