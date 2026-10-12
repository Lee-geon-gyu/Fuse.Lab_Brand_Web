<?php
/**
 * 메인 견적문의 폼(index.php #contact) 제출 처리.
 *   - fetch 요청(Accept: application/json): JSON {ok, message, errors?} 응답
 *   - 일반 POST(JS 없음): flash 메시지를 남기고 /#contact 로 redirect
 */
require __DIR__ . '/app/bootstrap.php';

$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

/** 응답 후 종료. 실패 시 입력값을 flash 로 넘겨 JS 없는 폼에서도 다시 채울 수 있게 한다 */
function inquiry_respond(bool $wantsJson, bool $ok, string $message, array $errors = [], array $old = []): never
{
    if ($wantsJson) {
        http_response_code($ok ? 200 : 422);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'message' => $message, 'errors' => (object) $errors], JSON_UNESCAPED_UNICODE);
        exit;
    }
    flash('inquiry', json_encode(['ok' => $ok, 'message' => $message, 'old' => $ok ? [] : $old], JSON_UNESCAPED_UNICODE));
    redirect('/#contact');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/#contact');
}

if (!csrf_valid()) {
    inquiry_respond($wantsJson, false, '요청이 만료되었습니다. 페이지를 새로고침한 뒤 다시 시도해 주세요.');
}

$successMessage = '문의가 접수되었습니다. 확인 후 빠르게 연락드리겠습니다.';

// 봇이 채우는 숨김 필드: 저장하지 않고 성공처럼 응답
$honeypot = $_POST['website'] ?? '';
if (!is_string($honeypot) || $honeypot !== '') {
    inquiry_respond($wantsJson, true, $successMessage);
}

[$data, $errors] = inquiry_validate($_POST);
if ($errors) {
    inquiry_respond($wantsJson, false, reset($errors), $errors, $data);
}

$lastAt = (int) ($_SESSION['inquiry_last_at'] ?? 0);
$wait = INQUIRY_SUBMIT_INTERVAL - (time() - $lastAt);
if ($wait > 0) {
    inquiry_respond($wantsJson, false, "잠시 후 다시 시도해 주세요. ({$wait}초 후 가능)", [], $data);
}

try {
    inquiry_create($data, (string) ($_SERVER['REMOTE_ADDR'] ?? ''));
} catch (PDOException $ex) {
    error_log('inquiry_create 실패: ' . $ex->getMessage());
    inquiry_respond($wantsJson, false, '문의 저장 중 오류가 발생했습니다. 잠시 후 다시 시도해 주세요.', [], $data);
}
$_SESSION['inquiry_last_at'] = time();

inquiry_respond($wantsJson, true, $successMessage);
