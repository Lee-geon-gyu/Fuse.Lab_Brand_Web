<?php
/**
 * 사용자 챗봇을 처음 열 때 위젯이 POST 로 호출 → 오늘 챗봇 사용 세션 +1 (세션당 하루 1회)
 * POST csrf_token 필요. 응답: { ok: bool }
 */
require __DIR__ . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}
if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['ok' => false]);
    exit;
}

track_chatbot_use();
echo json_encode(['ok' => true]);
