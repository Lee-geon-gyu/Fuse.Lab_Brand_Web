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
require __DIR__ . '/csrf.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/posts.php';
require __DIR__ . '/board-tables.php';
require __DIR__ . '/slots.php';
require __DIR__ . '/subpages.php';
require __DIR__ . '/stats.php';
require __DIR__ . '/inquiries.php';
require __DIR__ . '/portfolios.php';
require __DIR__ . '/chatbot.php';
require __DIR__ . '/mail.php';

/** HTML 출력용 이스케이프 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/** 회사 연락처: footer·챗봇 위젯·챗봇 AI 프롬프트가 같은 값을 쓴다 */
function site_contact(): array
{
    return [
        'email' => 'hello@fuselab.example',
        'phone' => '02-0000-2026',
        'address' => '서울특별시 성동구 성수이로 00길 00, 4F',
    ];
}

/** 절대 주소 기준. 운영은 설정의 site_url(Host 헤더 조작 방지), 비어 있으면(로컬) 요청 호스트 */
function site_url(): string
{
    $siteUrl = rtrim((string) app_config()['site_url'], '/');
    if ($siteUrl !== '') {
        return $siteUrl;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

/** 사이트 내부 CSS·JS 경로에 수정 시각을 붙여 배포 후 브라우저 캐시에 옛 파일이 남지 않게 한다. 외부 주소는 그대로 */
function asset(string $path): string
{
    $file = dirname(__DIR__) . parse_url($path, PHP_URL_PATH);
    if (!str_starts_with($path, '/') || str_starts_with($path, '//') || !is_file($file)) {
        return $path;
    }
    return $path . (str_contains($path, '?') ? '&' : '?') . 'v=' . filemtime($file);
}

/** 다음 요청에서 한 번만 보여줄 메시지. 인자 2개면 저장, 1개면 꺼내고 삭제 */
function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
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
