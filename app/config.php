<?php
/**
 * 설정값.
 * - 로컬(도커): docker-compose.yml 의 환경변수 사용
 * - 카페24: 환경변수가 없으므로 app/config.local.php 에 값을 넣어 업로드 (git 제외)
 */

$config = [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: '',
        'user' => getenv('DB_USER') ?: '',
        'pass' => getenv('DB_PASS') ?: '',
    ],
    'debug' => getenv('APP_DEBUG') === '1',
    // 관리자 회원가입 승인 코드. 비어 있으면 회원가입 불가
    'signup_code' => getenv('ADMIN_SIGNUP_CODE') ?: '',
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, require $local);
}

return $config;
