<?php
/**
 * 게시판 JSON API (관리자 전용). 응답: { ok: bool, message?: string, ... }
 *   GET  ?action=list                 → { posts: [...] }
 *   GET  ?action=get&id=              → { post: {...} }
 *   POST action=save   (multipart)    → id?, category, title, eyebrow, portfolio_id, content, visible, image, remove_files[], files[]
 *   POST action=bulk                  → ids[], op = show | hide | delete
 *   POST action=delete                → id
 * POST 는 모두 csrf_token 필요.
 */
require __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fail(string $message, int $status = 400): never
{
    respond(['ok' => false, 'message' => $message], $status);
}

/** $_FILES[name][] 다중 업로드를 파일별 배열로 */
function uploaded_list(string $name): array
{
    $files = $_FILES[$name] ?? null;
    if (!is_array($files) || !is_array($files['name'] ?? null)) {
        return [];
    }
    $list = [];
    foreach ($files['name'] as $index => $originalName) {
        if ($files['error'][$index] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $list[] = [
            'name' => $originalName,
            'tmp_name' => $files['tmp_name'][$index],
            'size' => $files['size'][$index],
            'error' => $files['error'][$index],
        ];
    }
    return $list;
}

$admin = current_admin();
if ($admin === null) {
    fail('로그인이 필요합니다.', 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    if ($action === 'list') {
        respond(['ok' => true, 'posts' => post_list()]);
    }
    if ($action === 'get') {
        $post = post_find((int) ($_GET['id'] ?? 0));
        $post ?? fail('게시글을 찾을 수 없습니다.', 404);
        respond(['ok' => true, 'post' => $post]);
    }
    fail('알 수 없는 요청입니다.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('허용되지 않는 요청입니다.', 405);
}

// post_max_size 를 넘으면 $_POST 가 비어서 CSRF 토큰도 사라진다
if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    fail('전송 용량이 너무 큽니다. 파일 크기를 줄여 주세요.', 413);
}
if (!csrf_valid()) {
    fail('요청이 만료되었습니다. 새로고침 후 다시 시도해 주세요.', 403);
}

$action = $_POST['action'] ?? '';

if ($action === 'bulk') {
    $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
    $op = $_POST['op'] ?? '';
    if (!$ids) {
        fail('먼저 게시글을 선택해 주세요.');
    }
    if ($op === 'delete') {
        $count = post_delete($ids);
    } elseif ($op === 'show' || $op === 'hide') {
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $stmt = db()->prepare("UPDATE posts SET is_visible = ? WHERE id IN ($marks)");
        $stmt->execute([$op === 'show' ? 1 : 0, ...$ids]);
        $count = count($ids);
    } else {
        fail('알 수 없는 요청입니다.');
    }
    respond(['ok' => true, 'count' => $count]);
}

if ($action === 'delete') {
    post_delete([(int) ($_POST['id'] ?? 0)]) || fail('게시글을 찾을 수 없습니다.', 404);
    respond(['ok' => true]);
}

if ($action !== 'save') {
    fail('알 수 없는 요청입니다.');
}

$id = (int) ($_POST['id'] ?? 0);
$board = (string) ($_POST['category'] ?? '');
$title = trim((string) ($_POST['title'] ?? ''));
$eyebrow = trim((string) ($_POST['eyebrow'] ?? ''));
$portfolioId = (int) ($_POST['portfolio_id'] ?? 0);
$content = trim((string) ($_POST['content'] ?? ''));
$visible = ($_POST['visible'] ?? '') === 'true' ? 1 : 0;
$removeFiles = array_map('intval', (array) ($_POST['remove_files'] ?? []));
$newImage = $_FILES['image'] ?? null;
$hasNewImage = is_array($newImage) && ($newImage['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

$existing = null;
if ($id > 0) {
    $stmt = db()->prepare('SELECT id, image FROM posts WHERE id = ?');
    $stmt->execute([$id]);
    $existing = $stmt->fetch() ?: fail('게시글을 찾을 수 없습니다.', 404);
}

if (!isset(POST_BOARDS[$board])) {
    fail('게시판을 선택해 주세요.');
}
if ($title === '' || $content === '') {
    fail('제목과 내용을 입력해 주세요.');
}
if (mb_strlen($title) > 120) {
    fail('제목은 120자 이내로 입력해 주세요.');
}
if (mb_strlen($eyebrow) > 60) {
    fail('상단 문구는 60자 이내로 입력해 주세요.');
}
if ($board === 'portfolio' && !$hasNewImage && empty($existing['image'])) {
    fail('포트폴리오는 배경 이미지가 필요합니다.');
}
if ($board !== 'portfolio') {
    $portfolioId = 0;
} elseif ($portfolioId > 0 && portfolio_find($portfolioId) === null) {
    fail('연결할 포트폴리오를 찾을 수 없습니다.');
}
$portfolioId = $portfolioId > 0 ? $portfolioId : null;

// 파일을 먼저 저장하고, DB 실패 시 새로 올린 파일만 지운다
$stored = [];
try {
    $image = $existing['image'] ?? null;
    if ($hasNewImage) {
        $stored[] = $name = post_store_upload($newImage, true);
        $image = post_upload_url($name);
    }
    $attachments = [];
    foreach (uploaded_list('files') as $file) {
        $stored[] = $name = post_store_upload($file, false);
        $attachments[] = [$file['name'], $name, (int) $file['size']];
    }
} catch (RuntimeException $exception) {
    array_map('post_delete_upload', $stored);
    fail($exception->getMessage());
}

$pdo = db();
$removedNames = [];
try {
    $pdo->beginTransaction();
    if ($existing) {
        $pdo->prepare('UPDATE posts SET board = ?, title = ?, eyebrow = ?, portfolio_id = ?, content = ?, image = ?, is_visible = ? WHERE id = ?')
            ->execute([$board, $title, $eyebrow, $portfolioId, $content, $image, $visible, $id]);
    } else {
        $pdo->prepare('INSERT INTO posts (board, title, eyebrow, portfolio_id, content, image, is_visible, admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$board, $title, $eyebrow, $portfolioId, $content, $image, $visible, $admin['id']]);
        $id = (int) $pdo->lastInsertId();
    }

    if ($removeFiles) {
        $marks = implode(',', array_fill(0, count($removeFiles), '?'));
        $stmt = $pdo->prepare("SELECT stored_name FROM post_files WHERE post_id = ? AND id IN ($marks)");
        $stmt->execute([$id, ...$removeFiles]);
        $removedNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $pdo->prepare("DELETE FROM post_files WHERE post_id = ? AND id IN ($marks)")->execute([$id, ...$removeFiles]);
    }

    $insertFile = $pdo->prepare('INSERT INTO post_files (post_id, original_name, stored_name, size) VALUES (?, ?, ?, ?)');
    foreach ($attachments as [$originalName, $storedName, $size]) {
        $insertFile->execute([$id, mb_substr($originalName, 0, 255), $storedName, $size]);
    }
    $pdo->commit();
} catch (PDOException $exception) {
    $pdo->rollBack();
    array_map('post_delete_upload', $stored);
    throw $exception;
}

array_map('post_delete_upload', $removedNames);
if ($hasNewImage && !empty($existing['image'])) {
    post_delete_upload($existing['image']);
}

respond(['ok' => true, 'id' => $id]);
