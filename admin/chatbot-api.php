<?php
/**
 * 챗봇 설정 JSON API (관리자 전용). 응답: { ok: bool, message?: string, settings?: {...} }
 *   POST action=save   (multipart) → aiName, guideText, themeColor, tone, welcomeMessage, icon(파일)?, remove_icon=1?
 *   POST action=delete             → 설정 행 삭제(기본값 복귀) + 업로드 아이콘 파일 삭제
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

/** JS 폼 필드 이름으로 바꾼 설정 */
function settings_payload(): array
{
    $settings = chatbot_settings();
    return [
        'aiName' => $settings['ai_name'],
        'guideText' => $settings['guide_text'],
        'themeColor' => $settings['theme_color'],
        'tone' => $settings['tone'],
        'welcomeMessage' => $settings['welcome_message'],
        'iconData' => $settings['icon'] ?? '',
        'iconName' => '',
    ];
}

if (current_admin() === null) {
    fail('로그인이 필요합니다.', 401);
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

if ($action === 'delete') {
    chatbot_reset();
    respond(['ok' => true, 'settings' => settings_payload()]);
}

if ($action !== 'save') {
    fail('알 수 없는 요청입니다.');
}

try {
    $values = chatbot_validate($_POST);
} catch (RuntimeException $exception) {
    fail($exception->getMessage());
}

$file = $_FILES['icon'] ?? null;
$hasNewIcon = is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
$icon = false;
if ($hasNewIcon) {
    try {
        $icon = chatbot_store_icon($file);
    } catch (RuntimeException $exception) {
        fail($exception->getMessage());
    }
} elseif (($_POST['remove_icon'] ?? '') === '1') {
    $icon = null;
}

try {
    chatbot_save($values, $icon);
} catch (PDOException $exception) {
    if (is_string($icon)) {
        post_delete_upload($icon);
    }
    throw $exception;
}

respond(['ok' => true, 'settings' => settings_payload()]);
