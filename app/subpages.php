<?php
/**
 * 서브페이지 (관리자 > 서브페이지 관리).
 * - 기존 PHP 페이지(portfolio.php 등): 본문은 코드 그대로, 관리자가 HTML 블록(본문 아래)·CSS·JS 를 덧붙이고 slot() 문구를 바꾼다
 * - 만든 페이지(/page/{slug}): 관리자가 HTML·CSS·JS 전체를 작성 (page.php 가 사이트 header·footer 안에 출력)
 * subpages 테이블: 기존 페이지는 file, 만든 페이지는 slug 로 구분. 기존 페이지는 처음 저장할 때 행이 생긴다.
 * HTML·CSS·JS 는 관리자 입력을 그대로 출력한다 (관리자만 입력 가능).
 */

const SUBPAGE_SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
const SUBPAGE_SLUG_MAX = 60;
const SUBPAGE_TITLE_MAX = 100;
const SUBPAGE_CODE_MAX = 200000;
/** 서브페이지 목록에서 제외할 사용자 PHP (메인, 만든 페이지 출력용) */
const SUBPAGE_EXCLUDED_FILES = ['index.php', 'page.php'];
/** 소스 저장 전 백업을 파일별로 몇 개까지 남길지 (app/storage/source-backups) */
const SUBPAGE_BACKUP_KEEP = 20;

/** 사이트 레이아웃(view('site/head'))을 쓰는 루트 PHP 페이지 */
function subpage_php_files(): array
{
    $files = [];
    foreach (glob(dirname(__DIR__) . '/*.php') ?: [] as $path) {
        $file = basename($path);
        if (!in_array($file, SUBPAGE_EXCLUDED_FILES, true) && str_contains((string) file_get_contents($path), "view('site/head'")) {
            $files[] = $file;
        }
    }
    // 확장자 뺀 이름순 (portfolio → portfolio-detail)
    usort($files, static fn (string $a, string $b): int => strcmp(pathinfo($a, PATHINFO_FILENAME), pathinfo($b, PATHINFO_FILENAME)));
    return $files;
}

/** 기존 PHP 페이지의 기본 이름: page_title 슬롯 기본값, 없으면 파일명 */
function subpage_default_title(string $file): string
{
    return slot_scan($file)['page_title']['default'] ?? $file;
}

function subpage_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM subpages WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function subpage_find_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM subpages WHERE slug = ?');
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

/** 기존 PHP 페이지의 행. 아직 저장한 적 없으면 빈 기본 행 (id = null) */
function subpage_for_file(string $file): ?array
{
    if (!in_array($file, subpage_php_files(), true)) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM subpages WHERE file = ?');
    $stmt->execute([$file]);
    return $stmt->fetch() ?: [
        'id' => null, 'file' => $file, 'slug' => null, 'title' => subpage_default_title($file),
        'html' => '', 'css' => '', 'js' => '', 'is_visible' => 1,
    ];
}

/** 사용자 화면 주소 */
function subpage_url(array $page): string
{
    return $page['file'] !== null ? '/' . $page['file'] : '/page/' . $page['slug'];
}

/** 관리자 편집 화면 주소 */
function subpage_edit_url(array $page): string
{
    return $page['file'] !== null
        ? '/admin/subpage-edit.php?file=' . rawurlencode($page['file'])
        : '/admin/subpage-edit.php?id=' . (int) $page['id'];
}

/**
 * 관리 목록: 기존 PHP 페이지(파일 순) + 만든 페이지(만든 순)
 * @return list<array> subpages 행 + kind('php'|'custom'), slot_count
 */
function subpage_list(): array
{
    $rows = db()->query('SELECT * FROM subpages ORDER BY created_at, id')->fetchAll();
    $byFile = array_column(array_filter($rows, static fn (array $row): bool => $row['file'] !== null), null, 'file');
    $list = [];
    foreach (subpage_php_files() as $file) {
        $page = $byFile[$file] ?? subpage_for_file($file);
        $list[] = $page + ['kind' => 'php', 'slot_count' => count(slot_scan($file))];
    }
    foreach ($rows as $row) {
        if ($row['slug'] !== null) {
            $list[] = $row + ['kind' => 'custom', 'slot_count' => 0];
        }
    }
    return $list;
}

/** 제목·주소 검사 @throws RuntimeException */
function subpage_clean_title(string $title): string
{
    $title = trim($title);
    if ($title === '' || mb_strlen($title) > SUBPAGE_TITLE_MAX) {
        throw new RuntimeException('페이지 이름은 1~' . SUBPAGE_TITLE_MAX . '자로 입력해 주세요.');
    }
    return $title;
}

/** @throws RuntimeException */
function subpage_clean_slug(string $slug, int $exceptId = 0): string
{
    $slug = strtolower(trim($slug));
    if (strlen($slug) > SUBPAGE_SLUG_MAX || !preg_match(SUBPAGE_SLUG_PATTERN, $slug)) {
        throw new RuntimeException('주소는 영소문자·숫자·하이픈(-)으로 ' . SUBPAGE_SLUG_MAX . '자 이내로 입력해 주세요.');
    }
    $existing = subpage_find_slug($slug);
    if ($existing !== null && (int) $existing['id'] !== $exceptId) {
        throw new RuntimeException("이미 쓰는 주소입니다: /page/{$slug}");
    }
    return $slug;
}

/** 새 페이지 (빈 내용, 숨김 상태로 시작) → id @throws RuntimeException */
function subpage_create(string $slug, string $title): int
{
    $slug = subpage_clean_slug($slug);
    $title = subpage_clean_title($title);
    db()->prepare("INSERT INTO subpages (slug, title, html, css, js, is_visible) VALUES (?, ?, '', '', '', 0)")
        ->execute([$slug, $title]);
    return (int) db()->lastInsertId();
}

/**
 * 저장. $page = subpage_find()/subpage_for_file() 결과. 기존 PHP 페이지는 slots 도 함께 저장한다.
 * @throws RuntimeException
 */
function subpage_save(array $page, array $input): void
{
    $title = subpage_clean_title((string) ($input['title'] ?? ''));
    $code = [];
    foreach (['html', 'css', 'js'] as $field) {
        $code[$field] = str_replace("\r\n", "\n", (string) ($input[$field] ?? ''));
        if (strlen($code[$field]) > SUBPAGE_CODE_MAX) {
            throw new RuntimeException(strtoupper($field) . ' 은 ' . number_format(SUBPAGE_CODE_MAX) . '바이트 이내로 입력해 주세요.');
        }
    }

    if ($page['file'] !== null) {
        slot_save($page['file'], (array) ($input['slots'] ?? []));
        db()->prepare(
            'INSERT INTO subpages (file, title, html, css, js, is_visible) VALUES (?, ?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE title = VALUES(title), html = VALUES(html), css = VALUES(css), js = VALUES(js)'
        )->execute([$page['file'], $title, $code['html'], $code['css'], $code['js']]);
        return;
    }

    $slug = subpage_clean_slug((string) ($input['slug'] ?? ''), (int) $page['id']);
    db()->prepare('UPDATE subpages SET slug = ?, title = ?, html = ?, css = ?, js = ?, is_visible = ? WHERE id = ?')
        ->execute([$slug, $title, $code['html'], $code['css'], $code['js'], empty($input['is_visible']) ? 0 : 1, $page['id']]);
}

/** 만든 페이지 삭제 (기존 PHP 페이지는 덧붙인 코드만 비우려면 내용을 지우고 저장) */
function subpage_delete(int $id): void
{
    db()->prepare('DELETE FROM subpages WHERE id = ? AND slug IS NOT NULL')->execute([$id]);
}

/**
 * 지금 요청의 서브페이지 (site/head·footer 가 CSS·HTML·JS 를 붙일 때 사용).
 * page.php 는 subpage_use() 로 지정하고, 기존 PHP 페이지는 실행 중인 파일명으로 찾는다.
 */
function subpage_current(?array $use = null): ?array
{
    static $current = false;
    if ($use !== null) {
        $current = $use;
    }
    if ($current === false) {
        $stmt = db()->prepare('SELECT * FROM subpages WHERE file = ?');
        $stmt->execute([basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''))]);
        $current = $stmt->fetch() ?: null;
    }
    return $current;
}

/** <style>·<script> 안에 그대로 넣을 때 태그가 닫히지 않게 */
function subpage_inline(string $code, string $tag): string
{
    return str_ireplace('</' . $tag, '<\\/' . $tag, $code);
}

// ---- 기존 PHP 페이지 소스 직접 편집 ----
// 관리자가 서버의 PHP 파일을 바로 고친다 (관리자 계정 = 서버 코드 실행 권한). config 'source_edit' => false 로 끌 수 있다.
// 저장 전: 다른 곳에서 바뀌었는지(해시) 확인 → PHP 문법 검사 → 기존 파일 백업 → 임시 파일에 쓰고 교체.
// 서버에서 고친 내용은 git 에 없으므로 필요하면 FTP 로 받아 커밋한다.

function subpage_source_enabled(): bool
{
    return (bool) (app_config()['source_edit'] ?? true);
}

/** 편집 가능한 기존 PHP 페이지의 실제 경로 @throws RuntimeException */
function subpage_source_path(string $file): string
{
    if (!subpage_source_enabled()) {
        throw new RuntimeException('소스 편집이 꺼져 있습니다 (config source_edit).');
    }
    if (!in_array($file, subpage_php_files(), true)) {
        throw new RuntimeException('편집할 수 없는 파일입니다.');
    }
    return dirname(__DIR__) . '/' . $file;
}

function subpage_source(string $file): string
{
    return (string) file_get_contents(subpage_source_path($file));
}

function subpage_backup_dir(): string
{
    return __DIR__ . '/storage/source-backups';
}

/** 파일 백업 목록 (최신순) @return list<array{name: string, time: int, size: int}> */
function subpage_source_backups(string $file): array
{
    $backups = [];
    foreach (glob(subpage_backup_dir() . '/' . $file . '.*.bak') ?: [] as $path) {
        $backups[] = ['name' => basename($path), 'time' => (int) filemtime($path), 'size' => (int) filesize($path)];
    }
    usort($backups, static fn (array $a, array $b): int => strcmp($b['name'], $a['name']));
    return $backups;
}

/** 현재 파일을 백업하고 오래된 백업 정리 @throws RuntimeException */
function subpage_source_backup(string $file, string $current): void
{
    $dir = subpage_backup_dir();
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('백업 폴더를 만들 수 없습니다 (app/storage 쓰기 권한 확인).');
    }
    $name = $file . '.' . date('Ymd-His') . '-' . bin2hex(random_bytes(2)) . '.bak';
    if (@file_put_contents($dir . '/' . $name, $current, LOCK_EX) === false) {
        throw new RuntimeException('백업 파일을 쓸 수 없습니다 (app/storage/source-backups 쓰기 권한 확인). 소스는 저장하지 않았습니다.');
    }
    foreach (array_slice(subpage_source_backups($file), SUBPAGE_BACKUP_KEEP) as $old) {
        @unlink($dir . '/' . $old['name']);
    }
}

/** 문법 검사 후 교체 (백업 포함) @throws RuntimeException */
function subpage_source_write(string $file, string $code): void
{
    try {
        token_get_all($code, TOKEN_PARSE);
    } catch (ParseError $error) {
        throw new RuntimeException("PHP 문법 오류 ({$error->getLine()}번째 줄): {$error->getMessage()} — 저장하지 않았습니다.");
    }
    $path = subpage_source_path($file);
    subpage_source_backup($file, (string) file_get_contents($path));
    $tmp = $path . '.tmp-' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, $code, LOCK_EX) === false || !@rename($tmp, $path)) {
        @unlink($tmp);
        throw new RuntimeException('파일을 쓸 수 없습니다 (서버 쓰기 권한 확인).');
    }
    if (function_exists('opcache_invalidate')) {
        opcache_invalidate($path, true);
    }
}

/**
 * 편집 화면에서 보낸 소스 저장. $hash = 편집을 시작할 때 파일의 sha1 (그 사이 바뀌었으면 거부).
 * @return bool 바뀐 내용이 있어 저장했는지
 * @throws RuntimeException
 */
function subpage_source_save(string $file, string $code, string $hash): bool
{
    $current = subpage_source($file);
    if (!hash_equals(sha1($current), $hash)) {
        throw new RuntimeException('편집하는 동안 파일이 바뀌었습니다. 소스를 복사해 두고 새로고침한 뒤 다시 적용해 주세요.');
    }
    // 브라우저는 줄바꿈을 CRLF 로 보내므로 파일의 원래 줄바꿈(LF/CRLF)에 맞춘다 (안 고친 줄이 diff 에 잡히지 않게)
    $code = str_replace("\r\n", "\n", $code);
    if (str_contains($current, "\r\n")) {
        $code = str_replace("\n", "\r\n", $code);
    }
    if ($code === $current) {
        return false;
    }
    if (strlen($code) > SUBPAGE_CODE_MAX) {
        throw new RuntimeException('소스는 ' . number_format(SUBPAGE_CODE_MAX) . '바이트 이내여야 합니다.');
    }
    subpage_source_write($file, $code);
    return true;
}

/** 백업으로 되돌림 (지금 파일도 백업) @throws RuntimeException */
function subpage_source_restore(string $file, string $backup): void
{
    $names = array_column(subpage_source_backups($file), 'name');
    if (!in_array($backup, $names, true)) {
        throw new RuntimeException('백업을 찾을 수 없습니다.');
    }
    subpage_source_write($file, (string) file_get_contents(subpage_backup_dir() . '/' . $backup));
}
