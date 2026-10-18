<?php
/**
 * 로그인 화면 ID/비밀번호 찾기 JSON 엔드포인트 (POST + CSRF).
 * type=id       : email
 * type=password : loginId, email
 * 계정 존재 여부와 무관하게 같은 문구로 응답한다 (계정 노출 방지).
 */
require __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

function recover_respond(int $status, bool $ok, string $message): never
{
    http_response_code($status);
    echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    recover_respond(405, false, '잘못된 요청입니다.');
}
if (!csrf_valid()) {
    recover_respond(403, false, '요청이 만료되었습니다. 페이지를 새로고침한 뒤 다시 시도해 주세요.');
}

$type = (string) ($_POST['type'] ?? '');
$email = trim((string) ($_POST['email'] ?? ''));
$loginId = trim((string) ($_POST['loginId'] ?? ''));

if ($type !== 'id' && $type !== 'password') {
    recover_respond(400, false, '잘못된 요청입니다.');
}
if (!admin_email_valid($email)) {
    recover_respond(422, false, '올바른 이메일을 입력해 주세요.');
}
if ($type === 'password' && !preg_match(ADMIN_LOGIN_ID_PATTERN, $loginId)) {
    recover_respond(422, false, 'ID를 올바르게 입력해 주세요.');
}

// 같은 세션 연속 요청 제한
$last = (int) ($_SESSION['admin_recovery_at'] ?? 0);
$wait = ADMIN_RECOVERY_INTERVAL_SECONDS - (time() - $last);
if ($wait > 0) {
    recover_respond(429, false, $wait . '초 후에 다시 시도해 주세요.');
}
$_SESSION['admin_recovery_at'] = time();

if ($type === 'id') {
    send_admin_id_reminder($email);
    recover_respond(200, true, '입력한 이메일로 가입된 계정이 있으면 메일을 보냈습니다.');
}

$resetUrl = site_url() . '/admin/reset-password.php';
request_admin_password_reset($loginId, $email, $resetUrl);
recover_respond(200, true, '입력한 ID와 이메일이 일치하는 계정이 있으면 재설정 메일을 보냈습니다.');
