<?php
/**
 * 게시판 (boards / posts / post_files) 공통 함수.
 * 게시판 종류는 boards 테이블에서 관리한다 (관리자 > 게시판 관리). portfolio 는 메인 슬라이드용 고정 게시판.
 * 업로드 파일은 웹 루트의 uploads/posts/ 에 무작위 이름으로 저장한다 (uploads/.htaccess 가 스크립트 실행 차단).
 */

/** 메인 포트폴리오 슬라이드 게시판 키 (삭제 불가, 이름 변경만 가능) */
const BOARD_PORTFOLIO = 'portfolio';
/** 포트폴리오 목록·상세(portfolios 테이블) 게시판 키. 글은 /admin/portfolios.php 에서 관리 (삭제 불가) */
const BOARD_PORTFOLIO_SUB = 'portfolio-sub';
const BOARD_KEY_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
const BOARD_KEY_MAX = 20;
const BOARD_NAME_MAX = 50;
/** 새로 만들 수 있는 게시판 유형 => 표시명. post = 게시글 목록, table = 관리자가 열·행을 편집하는 표 */
const BOARD_TYPES = [
    'post' => '글 목록',
    'table' => '표',
];
/** 화면 표시용 전체 유형 (portfolio = 고정 게시판 portfolio-sub 전용, 새로 만들 수 없음) */
const BOARD_TYPE_LABELS = BOARD_TYPES + ['portfolio' => '포트폴리오'];

const POST_IMAGE_MAX_BYTES = 10 * 1024 * 1024;
const POST_FILE_MAX_BYTES = 20 * 1024 * 1024;
const POST_IMAGE_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
];
const POST_FILE_EXTENSIONS = [
    'pdf', 'zip', 'txt', 'csv', 'hwp', 'hwpx',
    'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
    'jpg', 'jpeg', 'png', 'gif', 'webp',
];

/**
 * 게시판 목록 [키 => 이름] (순서 번호 → 만든 순).
 * $type 을 주면 그 유형만 (게시글 화면은 'post' 만 써야 한다)
 */
function board_list(?string $type = null): array
{
    if ($type === null) {
        return db()->query('SELECT board_key, name FROM boards ORDER BY sort_order, created_at, board_key')
            ->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    $stmt = db()->prepare('SELECT board_key, name FROM boards WHERE type = ? ORDER BY sort_order, created_at, board_key');
    $stmt->execute([$type]);
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

/** 게시판 관리 화면용: board_key, name, type, sort_order, post_count(글 목록 = 글 수, 표 = 행 수, 포트폴리오 = 항목 수) */
function board_admin_list(): array
{
    $rows = db()->query(
        'SELECT b.board_key, b.name, b.type, b.sort_order, t.row_data,
                (SELECT COUNT(*) FROM posts p WHERE p.board = b.board_key) AS post_count
         FROM boards b
         LEFT JOIN board_tables t ON t.board_key = b.board_key
         ORDER BY b.sort_order, b.created_at, b.board_key'
    )->fetchAll();
    foreach ($rows as &$row) {
        if ($row['type'] === 'table') {
            $row['post_count'] = count(json_decode((string) $row['row_data'], true) ?: []);
        } elseif ($row['type'] === 'portfolio') {
            $row['post_count'] = (int) db()->query('SELECT COUNT(*) FROM portfolios')->fetchColumn();
        }
        unset($row['row_data']);
    }
    return $rows;
}

/** 게시판 글 목록 화면 주소 (유형별로 다름) */
function board_admin_url(string $key, string $type): string
{
    return match ($type) {
        'table' => '/admin/board-table.php?board=' . rawurlencode($key),
        'portfolio' => '/admin/portfolios.php',
        default => '/admin/board.php?board=' . rawurlencode($key),
    };
}

/** 게시판 유형 ('post' | 'table'), 없으면 null */
function board_type(string $key): ?string
{
    $stmt = db()->prepare('SELECT type FROM boards WHERE board_key = ?');
    $stmt->execute([$key]);
    $type = $stmt->fetchColumn();
    return $type === false ? null : $type;
}

function board_exists(string $key): bool
{
    $stmt = db()->prepare('SELECT 1 FROM boards WHERE board_key = ?');
    $stmt->execute([$key]);
    return (bool) $stmt->fetchColumn();
}

/**
 * 이름 앞뒤 공백 제거 + 길이·중복 검사 ($exceptKey = 이름을 바꾸는 자기 자신)
 * 이름이 겹치면 관리 화면에서 구분이 안 되므로 막는다 (예: 포트폴리오-main / 포트폴리오-sub)
 * @throws RuntimeException
 */
function board_clean_name(string $name, string $exceptKey = ''): string
{
    $name = trim($name);
    if ($name === '' || mb_strlen($name) > BOARD_NAME_MAX) {
        throw new RuntimeException('게시판 이름은 1~' . BOARD_NAME_MAX . '자로 입력해 주세요.');
    }
    $stmt = db()->prepare('SELECT 1 FROM boards WHERE name = ? AND board_key <> ?');
    $stmt->execute([$name, $exceptKey]);
    if ($stmt->fetchColumn()) {
        throw new RuntimeException("'{$name}' 이름의 게시판이 이미 있습니다. -main, -sub 처럼 구분해 주세요.");
    }
    return $name;
}

/** 새 게시판 (맨 뒤 순서) @throws RuntimeException 사용자에게 보여줄 메시지 */
function board_create(string $key, string $name, string $type = 'post'): void
{
    $key = strtolower(trim($key));
    if (strlen($key) > BOARD_KEY_MAX || !preg_match(BOARD_KEY_PATTERN, $key)) {
        throw new RuntimeException('게시판 키는 영소문자·숫자·하이픈(-)으로 ' . BOARD_KEY_MAX . '자 이내로 입력해 주세요.');
    }
    $name = board_clean_name($name);
    if (!isset(BOARD_TYPES[$type])) {
        throw new RuntimeException('게시판 유형을 선택해 주세요.');
    }
    if (board_exists($key)) {
        throw new RuntimeException('이미 있는 게시판 키입니다: ' . $key);
    }
    $next = (int) db()->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM boards')->fetchColumn();
    db()->prepare('INSERT INTO boards (board_key, name, type, sort_order) VALUES (?, ?, ?, ?)')
        ->execute([$key, $name, $type, $next]);
}

/** 이름 변경 @throws RuntimeException */
function board_rename(string $key, string $name): void
{
    $name = board_clean_name($name, $key);
    if (!board_exists($key)) {
        throw new RuntimeException('게시판을 찾을 수 없습니다.');
    }
    db()->prepare('UPDATE boards SET name = ? WHERE board_key = ?')->execute([$name, $key]);
}

/**
 * 게시판 삭제 + 그 게시판의 글·이미지·첨부(글 목록) 또는 표 데이터 삭제. 지운 글·행 수를 돌려준다.
 * @throws RuntimeException 포트폴리오 게시판이거나 없는 게시판
 */
function board_delete(string $key): int
{
    if ($key === BOARD_PORTFOLIO) {
        throw new RuntimeException('포트폴리오-main 게시판은 메인 슬라이드에 쓰이므로 삭제할 수 없습니다.');
    }
    if ($key === BOARD_PORTFOLIO_SUB) {
        throw new RuntimeException('포트폴리오-sub 게시판은 포트폴리오 목록·상세 페이지에 쓰이므로 삭제할 수 없습니다.');
    }
    if (!board_exists($key)) {
        throw new RuntimeException('게시판을 찾을 수 없습니다.');
    }
    $stmt = db()->prepare('SELECT id FROM posts WHERE board = ?');
    $stmt->execute([$key]);
    $deleted = post_delete($stmt->fetchAll(PDO::FETCH_COLUMN));
    $deleted += count(board_table($key)['rows']);
    db()->prepare('DELETE FROM board_tables WHERE board_key = ?')->execute([$key]);
    db()->prepare('DELETE FROM boards WHERE board_key = ?')->execute([$key]);
    return $deleted;
}

/** 이웃 게시판과 자리를 바꾸고 sort_order 를 1부터 다시 매긴다 */
function board_move(string $key, string $direction): bool
{
    // 숫자로만 된 키는 PHP 배열에서 int 가 되므로 문자열로 맞춘다
    $keys = array_map('strval', array_keys(board_list()));
    $index = array_search($key, $keys, true);
    $neighbor = $index === false ? false : ($direction === 'up' ? $index - 1 : $index + 1);
    if ($index === false || !isset($keys[$neighbor])) {
        return false;
    }
    [$keys[$index], $keys[$neighbor]] = [$keys[$neighbor], $keys[$index]];

    $pdo = db();
    $stmt = $pdo->prepare('UPDATE boards SET sort_order = ? WHERE board_key = ?');
    $pdo->beginTransaction();
    foreach ($keys as $position => $boardKey) {
        $stmt->execute([$position + 1, $boardKey]);
    }
    $pdo->commit();
    return true;
}

/** 업로드 저장 폴더 (파일시스템 경로) */
function post_upload_dir(): string
{
    return dirname(__DIR__) . '/uploads/posts';
}

/** 업로드 파일의 웹 경로 */
function post_upload_url(string $storedName): string
{
    return '/uploads/posts/' . $storedName;
}

/**
 * 메인 포트폴리오 슬라이드: 노출된 portfolio 글, 먼저 쓴 글이 앞.
 * link_id = 연결된 포트폴리오 상세 id (연결 없음·삭제·숨김이면 null)
 */
function portfolio_slides(): array
{
    return db()->query(
        "SELECT p.id, p.title, p.eyebrow, p.content, p.image, f.id AS link_id
         FROM posts p
         LEFT JOIN portfolios f ON f.id = p.portfolio_id AND f.is_visible = 1
         WHERE p.board = 'portfolio' AND p.is_visible = 1 AND p.image IS NOT NULL
         ORDER BY p.id"
    )->fetchAll();
}

/** 관리자 목록용 전체 글 (최신 글이 앞) */
function post_list(): array
{
    $rows = db()->query(
        'SELECT p.id, p.board, p.title, p.content, p.is_visible, p.views, p.created_at, a.name AS author
         FROM posts p LEFT JOIN admins a ON a.id = p.admin_id
         ORDER BY p.id DESC'
    )->fetchAll();

    return array_map(static fn (array $row): array => [
        'id' => (int) $row['id'],
        'category' => $row['board'],
        'title' => $row['title'],
        'content' => $row['content'],
        'author' => $row['author'] ?? '(삭제된 관리자)',
        'date' => date('m-d', strtotime($row['created_at'])),
        'views' => (int) $row['views'],
        'visible' => (bool) $row['is_visible'],
    ], $rows);
}

/** 편집 화면용 글 1건 + 첨부 목록 (없으면 null) */
function post_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, board, title, eyebrow, portfolio_id, content, image, is_visible FROM posts WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }

    $stmt = db()->prepare('SELECT id, original_name, stored_name FROM post_files WHERE post_id = ? ORDER BY id');
    $stmt->execute([$id]);

    return [
        'id' => (int) $row['id'],
        'category' => $row['board'],
        'title' => $row['title'],
        'eyebrow' => $row['eyebrow'],
        'portfolioId' => $row['portfolio_id'] === null ? '' : (string) $row['portfolio_id'],
        'content' => $row['content'],
        'image' => $row['image'],
        'visible' => (bool) $row['is_visible'],
        'files' => array_map(static fn (array $file): array => [
            'id' => (int) $file['id'],
            'name' => $file['original_name'],
            'url' => post_upload_url($file['stored_name']),
        ], $stmt->fetchAll()),
    ];
}

/**
 * $_FILES 항목 하나를 검증해 uploads/posts/ 로 옮기고 저장 이름을 돌려준다.
 * $imageOnly 면 실제 이미지 형식(jpg/png/webp/gif)만 허용.
 * @throws RuntimeException 사용자에게 보여줄 메시지
 */
function post_store_upload(array $file, bool $imageOnly): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('파일을 업로드하지 못했습니다. 용량을 확인해 주세요.');
    }

    if ($imageOnly) {
        if ($file['size'] > POST_IMAGE_MAX_BYTES) {
            throw new RuntimeException('이미지는 10MB 이하만 올릴 수 있습니다.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extension = POST_IMAGE_TYPES[$mime] ?? null;
        if ($extension === null || @getimagesize($file['tmp_name']) === false) {
            throw new RuntimeException('이미지는 JPG, PNG, WEBP, GIF 형식만 올릴 수 있습니다.');
        }
    } else {
        if ($file['size'] > POST_FILE_MAX_BYTES) {
            throw new RuntimeException('첨부파일은 20MB 이하만 올릴 수 있습니다.');
        }
        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, POST_FILE_EXTENSIONS, true)) {
            throw new RuntimeException('첨부할 수 없는 파일 형식입니다: ' . $file['name']);
        }
    }

    $dir = post_upload_dir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('업로드 폴더를 만들 수 없습니다.');
    }

    $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $storedName)) {
        throw new RuntimeException('파일을 저장하지 못했습니다.');
    }
    return $storedName;
}

/** uploads/posts/ 안의 파일이면 삭제 (aseets/ 등 다른 경로의 이미지는 건드리지 않음) */
function post_delete_upload(?string $urlOrName): void
{
    if ($urlOrName === null || $urlOrName === '') {
        return;
    }
    $prefix = '/uploads/posts/';
    $name = str_starts_with($urlOrName, $prefix) ? substr($urlOrName, strlen($prefix)) : $urlOrName;
    if (str_contains($urlOrName, '/') && !str_starts_with($urlOrName, $prefix)) {
        return;
    }
    if (preg_match('/^[a-f0-9]{32}\.[a-z0-9]+$/', $name)) {
        @unlink(post_upload_dir() . '/' . $name);
    }
}

/** 글 삭제 + 이미지·첨부 파일 정리 */
function post_delete(array $ids): int
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) {
        return 0;
    }
    $marks = implode(',', array_fill(0, count($ids), '?'));

    $stmt = db()->prepare("SELECT image FROM posts WHERE id IN ($marks)");
    $stmt->execute($ids);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = db()->prepare("SELECT stored_name FROM post_files WHERE post_id IN ($marks)");
    $stmt->execute($ids);
    $files = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = db()->prepare("DELETE FROM posts WHERE id IN ($marks)");
    $stmt->execute($ids);

    array_map('post_delete_upload', [...$images, ...$files]);
    return $stmt->rowCount();
}
