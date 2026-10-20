---
name: ftp-deploy
description: fuse.lab 사이트를 카페24(fuselab.mycafe24.com)에 FTP 로 배포할 때. "업로드", "배포", "라이브 반영", "ftp 올려" 요청에 사용
---

# 카페24 FTP 배포

스크립트: `.claude/skills/ftp-deploy/deploy.py` (Python 표준 라이브러리만 사용). 접속 정보는 저장소 루트 `.env` 의 `FTP_HOST`·`FTP_PORT`·`FTP_USER`·`FTP_PASSWORD`·`FTP_REMOTE_DIR`(=`/www`), 선택 `DEPLOY_URL`(기본 `https://{FTP_HOST}`)

## 절차

1. 배포 대상 변경을 커밋한다. 커밋 안 된 변경이 있으면 스크립트가 멈춤 (`--allow-dirty` 는 사용자가 원할 때만)
2. 목록 확인: `python .claude/skills/ftp-deploy/deploy.py --dry-run`
3. 업로드: `python .claude/skills/ftp-deploy/deploy.py`
   - 원격 `/www/.deploy-rev` 에 적힌 커밋부터 `HEAD` 까지 바뀐 파일만 올리고, 삭제된 파일은 원격에서도 지움. 끝나면 `.deploy-rev` 를 `HEAD` 로 갱신
   - `.deploy-rev` 가 없거나 `--all` 이면 배포 대상 전체 업로드 (삭제 없음)
   - `db/schema.sql` 이 바뀌었으면 `--schema` 추가 → 일회용 PHP 를 무작위 이름으로 올려 schema.sql 만 실행하고 바로 삭제 (`--schema-if-changed` 는 schema.sql 이 업로드 대상일 때만 적용)
4. 확인: 라이브 `/`, `/portfolio.php`, `/admin/login.php` 200, `/app/config.local.php`·`/db/schema.sql` 403. 화면을 바꾼 배포면 브라우저로 해당 페이지를 열어 콘솔 오류 확인

## 자동 배포 (GitHub Actions)

- `deploy` 브랜치 push → `.github/workflows/deploy.yml` 이 이 스크립트를 `--schema-if-changed` 로 실행. 접속 정보는 `.env` 대신 저장소 Secrets(같은 이름의 환경변수)
- 같은 `.deploy-rev` 를 쓰므로 로컬 수동 배포와 섞어 써도 됨. 단 로컬에서 push 안 한 커밋으로 배포하면 다음 Actions 배포가 그 커밋을 못 찾아 실패함 (Actions 에서 `all` 로 실행)

## 규칙

- 업로드 제외 (AGENTS.md 배포 항목): `docker/`, `docker-compose.yml`, `setup.*`, `.env*`, `.git*`, `*.md`, `.claude/`, `.agents/`, `.codex/`
- 서버 전용, 건드리지 않음: `app/config.local.php`(운영 DB·`site_url` 설정), `app/storage/`, `uploads/posts/`(운영 업로드 파일)
- FTP 로그인 홈이 이미 `/www` 다. 경로는 항상 `/www/...` 절대 경로로 쓴다 (상대 경로나 `/` 기준으로 올리면 웹 루트 밖에 올라감)
- `db/dump.sql` 은 운영에 다시 넣지 않는다 (운영 콘텐츠를 덮어씀). 첫 설치 때만 빈 DB 에 적용했음
- 운영 설정을 바꿔야 하면 `app/config.local.php` 를 로컬에 잠깐 만들어 `/www/app/` 에만 올리고 로컬 파일은 바로 지운다 (로컬에 남으면 도커가 그 값을 읽어 500)
- 운영 서버: PHP 8.4.21, MariaDB 10.6.17 (2026-10 확인)

## 가림막

- 원격 `/www/maintenance.html` 이 가림막. 켜려면 FTP 에서 `/www/index.php` → `index.php.off`, `/www/index.html` 을 백업 후 `maintenance.html` 을 `index.html` 로 복사. 끄려면 반대로 되돌린다 (`DirectoryIndex index.php index.html`)
- 사용자에게 확인받고 진행한다
