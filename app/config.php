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
    // 메일 보내는 사람 주소. 비어 있으면 no-reply@현재 호스트
    'mail_from' => getenv('MAIL_FROM') ?: '',
    // 사이트 고정 주소 (예: https://fuselab.co.kr). 비밀번호 재설정 메일 링크에 사용. 비어 있으면 요청 호스트
    'site_url' => getenv('SITE_URL') ?: '',
    // 관리자 > 서브페이지 편집에서 기존 PHP 페이지 소스 직접 수정 허용 (false 면 소스 탭 숨김)
    'source_edit' => getenv('SOURCE_EDIT') !== '0',
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, require $local);
}

return $config;
