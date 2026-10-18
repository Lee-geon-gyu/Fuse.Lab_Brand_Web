<?php
/**
 * CSRF 토큰 (세션당 1개). POST 폼에는 csrf_field(), 처리 쪽에서는 csrf_valid() 확인.
 */
function csrf_token(): string
{
    return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '" />';
}

function csrf_valid(): bool
{
    $sent = $_POST['csrf_token'] ?? '';
    return is_string($sent) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $sent);
}
