<?php
/**
 * 회원가입 ID 중복확인 (GET ?loginId=) → JSON { available: bool, message: string }
 */
require __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$loginId = trim((string) ($_GET['loginId'] ?? ''));

if (!preg_match(ADMIN_LOGIN_ID_PATTERN, $loginId)) {
    $result = ['available' => false, 'message' => 'ID는 영문, 숫자, . _ @ - 조합 4~50자로 입력해 주세요.'];
} elseif (admin_login_id_exists($loginId)) {
    $result = ['available' => false, 'message' => '이미 사용 중인 ID입니다.'];
} else {
    $result = ['available' => true, 'message' => '사용할 수 있는 ID입니다.'];
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
