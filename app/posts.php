<?php
/**
 * 게시판 (posts / post_files) 공통 함수.
 * 업로드 파일은 웹 루트의 uploads/posts/ 에 무작위 이름으로 저장한다 (uploads/.htaccess 가 스크립트 실행 차단).
 */

const POST_BOARDS = [
    'notice' => '공지사항',
    'event' => '이벤트',
    'resources' => '자료실',
    'portfolio' => '포트폴리오',
];

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

/** 메인 포트폴리오 슬라이드: 노출된 portfolio 글, 먼저 쓴 글이 앞 */
function portfolio_slides(): array
{
    return db()->query(
        "SELECT id, title, eyebrow, content, image FROM posts
         WHERE board = 'portfolio' AND is_visible = 1 AND image IS NOT NULL
         ORDER BY id"
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
    $stmt = db()->prepare('SELECT id, board, title, eyebrow, content, image, is_visible FROM posts WHERE id = ?');
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
