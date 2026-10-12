<?php
/**
 * 모든 PHP 페이지 최상단에서 require 한다.
 *   require __DIR__ . '/app/bootstrap.php';
 */

function app_config(): array
{
    static $config = null;
    return $config ??= require __DIR__ . '/config.php';
}

date_default_timezone_set('Asia/Seoul');
mb_internal_encoding('UTF-8');

$debug = app_config()['debug'];
ini_set('display_errors', $debug ? '1' : '0');
error_reporting(E_ALL);

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
session_start();

require __DIR__ . '/db.php';

/** HTML 출력용 이스케이프 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * app/views/{$name}.php 를 $vars 를 변수로 풀어 출력한다.
 *   view('site/head', ['pageTitle' => '포트폴리오']);
 */
function view(string $name, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    require __DIR__ . '/views/' . $name . '.php';
}
