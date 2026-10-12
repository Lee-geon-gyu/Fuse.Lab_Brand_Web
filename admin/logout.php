<?php
require __DIR__ . '/../app/bootstrap.php';

$_SESSION = [];
session_destroy();

header('Location: /admin/login.php');
exit;
