<?php
/**
 * 사용자 챗봇 AI 답변 (Claude). 위젯이 POST 로 호출한다.
 *   POST csrf_token, messages = JSON [{role: "user"|"assistant", content}] (사용자 질문으로 끝남)
 * 응답: { ok: true, reply } | { ok: false, message } — 실패하면 위젯이 규칙 기반 응답으로 대체한다.
 */
require __DIR__ . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function chat_respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chat_respond(['ok' => false, 'message' => '허용되지 않는 요청입니다.'], 405);
}
if (!csrf_valid()) {
    chat_respond(['ok' => false, 'message' => '요청이 만료되었습니다.'], 403);
}

try {
    $messages = chatbot_ai_messages(json_decode((string) ($_POST['messages'] ?? ''), true));
} catch (RuntimeException $exception) {
    chat_respond(['ok' => false, 'message' => $exception->getMessage()], 400);
}
if (!chatbot_ai_allow()) {
    chat_respond(['ok' => false, 'message' => '잠시 후 다시 질문해 주세요.'], 429);
}
// API 응답을 기다리는 동안 같은 세션의 다른 요청이 막히지 않게 세션을 먼저 닫는다
session_write_close();

try {
    chat_respond(['ok' => true, 'reply' => chatbot_ai_reply($messages)]);
} catch (RuntimeException $exception) {
    chat_respond(['ok' => false, 'message' => $exception->getMessage()], 503);
}
