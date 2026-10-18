<?php
// 카페24 업로드용: 이 파일을 config.local.php 로 복사해 값을 채운다. (git 에 올리지 않음)
// 카페24 DB 정보: 나의서비스관리 > DB 관리 (DB명/아이디는 보통 호스팅 아이디와 같음)
return [
    'db' => [
        'host' => 'localhost',
        'port' => '3306',
        'name' => 'CAFE24_ID',
        'user' => 'CAFE24_ID',
        'pass' => 'DB_PASSWORD',
    ],
    'debug' => false,
    // 관리자 회원가입 승인 코드 (추측하기 어려운 값). 관리자 등록이 끝나면 '' 로 비워 가입을 막아도 된다
    'signup_code' => 'CHANGE_ME',
    // 계정 찾기 메일의 보내는 사람 주소 (사이트 도메인 메일 권장)
    'mail_from' => 'no-reply@example.com',
    // 사이트 주소 (비밀번호 재설정 메일 링크·공유 썸네일 og:image 절대 주소용, 끝에 / 없이)
    'site_url' => 'https://example.com',
    // 관리자에서 기존 PHP 페이지 소스 직접 수정 허용. 관리자 계정이 털리면 서버 코드 실행까지 가능하므로 필요 없으면 false
    'source_edit' => true,
];
