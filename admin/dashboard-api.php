<?php
/**
 * 대시보드 JSON API (관리자 전용, 조회만). 응답: { ok: bool, message?: string, ... }
 *   GET ?action=stats&month=YYYY-MM → { stats: {month, daily[], visitorTotal, chatbotUsers, chatbotRate}, members: {total, month, today, activeRate} }
 *   GET ?action=lists                → { inquiries: [...], posts: [...], boards: {키: 라벨} }
 */
require __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (current_admin() === null) {
    respond(['ok' => false, 'message' => '로그인이 필요합니다.'], 401);
}

$action = $_GET['action'] ?? '';

if ($action === 'stats') {
    $monthStart = dashboard_parse_month((string) ($_GET['month'] ?? ''));
    if ($monthStart === null) {
        respond(['ok' => false, 'message' => '조회 월 형식이 올바르지 않습니다.'], 400);
    }
    respond([
        'ok' => true,
        'stats' => dashboard_month_stats($monthStart),
        'members' => dashboard_admin_stats($monthStart),
    ]);
}

if ($action === 'lists') {
    respond([
        'ok' => true,
        'inquiries' => dashboard_recent_inquiries(),
        'posts' => dashboard_recent_posts(),
        'boards' => POST_BOARDS,
    ]);
}

respond(['ok' => false, 'message' => '알 수 없는 요청입니다.'], 400);
