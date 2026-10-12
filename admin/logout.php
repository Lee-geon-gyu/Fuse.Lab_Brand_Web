<?php
require __DIR__ . '/../app/bootstrap.php';

// 로그아웃은 메뉴의 POST 폼으로만 처리 (외부 링크로 강제 로그아웃 방지)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    redirect('/admin/');
}

logout_admin();
redirect('/admin/login.php');
