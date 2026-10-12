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
];
