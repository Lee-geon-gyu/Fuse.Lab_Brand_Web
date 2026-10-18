<?php
/**
 * 챗봇 설정 (chatbot_settings 단일 행, id = 1). 행이 없으면 기본값을 쓴다.
 * 아이콘 파일은 게시판 업로드 함수(post_store_upload)로 uploads/posts/ 에 저장한다.
 */

/** 말투 값 => 관리자 화면 표시명 */
const CHATBOT_TONES = [
    'friendly' => '친근하게',
    'calm' => '차분하게',
    'warm' => '따듯하게',
    'concise' => '간결하게',
];

/** 아이콘으로 허용하는 이미지 (SVG 는 스크립트를 담을 수 있어 제외) */
const CHATBOT_ICON_TYPES = ['image/png', 'image/jpeg', 'image/webp'];
const CHATBOT_ICON_MAX_BYTES = 1024 * 1024;

/** @return array{ai_name: string, guide_text: string, icon: ?string, theme_color: string, tone: string, welcome_message: string} */
function chatbot_defaults(): array
{
    return [
        'ai_name' => 'fuse.lab AI',
        'guide_text' => '프로젝트 상담을 도와드려요',
        'icon' => null,
        'theme_color' => '#6a00ff',
        'tone' => 'friendly',
        'welcome_message' => '안녕하세요, 궁금하신 점을 편하게 물어보세요 😄',
    ];
}

/** 저장된 설정, 없으면 기본값 */
function chatbot_settings(): array
{
    $row = db()->query('SELECT ai_name, guide_text, icon, theme_color, tone, welcome_message FROM chatbot_settings WHERE id = 1')->fetch();
    if (!$row) {
        return chatbot_defaults();
    }
    if (!isset(CHATBOT_TONES[$row['tone']])) {
        $row['tone'] = 'friendly';
    }
    if (!chatbot_valid_color($row['theme_color'])) {
        $row['theme_color'] = chatbot_defaults()['theme_color'];
    }
    return $row;
}

function chatbot_valid_color(string $color): bool
{
    return preg_match('/^#[0-9a-fA-F]{6}$/', $color) === 1;
}

/** 테마 색상 위에 올릴 글자색 (밝은 색이면 검정, 어두운 색이면 흰색) */
function chatbot_contrast_color(string $hex): string
{
    [$r, $g, $b] = array_map('hexdec', str_split(substr($hex, 1), 2));
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    return $luminance > 0.62 ? '#111111' : '#ffffff';
}

/**
 * 입력 검증 후 정리된 값을 돌려준다. 문제가 있으면 RuntimeException(사용자용 메시지).
 * @return array{ai_name: string, guide_text: string, theme_color: string, tone: string, welcome_message: string}
 */
function chatbot_validate(array $input): array
{
    $aiName = trim((string) ($input['aiName'] ?? ''));
    $guideText = trim((string) ($input['guideText'] ?? ''));
    $themeColor = trim((string) ($input['themeColor'] ?? ''));
    $tone = (string) ($input['tone'] ?? '');
    $welcome = trim(str_replace("\r\n", "\n", (string) ($input['welcomeMessage'] ?? '')));

    if ($aiName === '') {
        throw new RuntimeException('AI 이름을 입력해 주세요.');
    }
    if (mb_strlen($aiName) > 50) {
        throw new RuntimeException('AI 이름은 50자 이내로 입력해 주세요.');
    }
    if (mb_strlen($guideText) > 200) {
        throw new RuntimeException('안내문구는 200자 이내로 입력해 주세요.');
    }
    if ($themeColor === '') {
        $themeColor = chatbot_defaults()['theme_color'];
    }
    if (!chatbot_valid_color($themeColor)) {
        throw new RuntimeException('테마 색상은 #6a00ff처럼 입력해 주세요.');
    }
    if (!isset(CHATBOT_TONES[$tone])) {
        throw new RuntimeException('말투를 선택해 주세요.');
    }
    if ($welcome === '') {
        throw new RuntimeException('환영 메세지를 입력해 주세요.');
    }
    if (mb_strlen($welcome) > 500) {
        throw new RuntimeException('환영 메세지는 500자 이내로 입력해 주세요.');
    }

    return [
        'ai_name' => $aiName,
        'guide_text' => $guideText,
        'theme_color' => strtolower($themeColor),
        'tone' => $tone,
        'welcome_message' => $welcome,
    ];
}

/** 업로드된 아이콘을 저장하고 URL 을 돌려준다 (PNG/JPEG/WEBP, 1MB 이하) */
function chatbot_store_icon(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name'])) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!in_array($mime, CHATBOT_ICON_TYPES, true)) {
            throw new RuntimeException('아이콘은 PNG, JPG, WEBP 형식만 올릴 수 있습니다.');
        }
        if ($file['size'] > CHATBOT_ICON_MAX_BYTES) {
            throw new RuntimeException('아이콘은 1MB 이하만 올릴 수 있습니다.');
        }
    }
    return post_upload_url(post_store_upload($file, true));
}

/**
 * 설정 저장. $icon: 새 아이콘 URL(string), 기존 유지(false), 아이콘 제거(null).
 * 교체·제거된 이전 아이콘 파일은 저장 후 삭제한다.
 */
function chatbot_save(array $values, string|false|null $icon): void
{
    $previous = db()->query('SELECT icon FROM chatbot_settings WHERE id = 1')->fetchColumn();
    $previous = $previous === false ? null : $previous;
    $nextIcon = $icon === false ? $previous : $icon;

    db()->prepare(
        'INSERT INTO chatbot_settings (id, ai_name, guide_text, icon, theme_color, tone, welcome_message)
         VALUES (1, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE ai_name = VALUES(ai_name), guide_text = VALUES(guide_text), icon = VALUES(icon),
             theme_color = VALUES(theme_color), tone = VALUES(tone), welcome_message = VALUES(welcome_message)'
    )->execute([
        $values['ai_name'],
        $values['guide_text'],
        $nextIcon,
        $values['theme_color'],
        $values['tone'],
        $values['welcome_message'],
    ]);

    if ($previous !== null && $previous !== $nextIcon) {
        post_delete_upload($previous);
    }
}

/** 설정 행 삭제 → 기본값으로 복귀, 업로드한 아이콘 파일도 삭제 */
function chatbot_reset(): void
{
    $icon = db()->query('SELECT icon FROM chatbot_settings WHERE id = 1')->fetchColumn();
    db()->prepare('DELETE FROM chatbot_settings WHERE id = ?')->execute([1]);
    if (is_string($icon)) {
        post_delete_upload($icon);
    }
}

// ---- AI 응답 (Anthropic Claude Messages API) ----
// 키가 없거나 API 호출이 실패하면 위젯은 js/chatbot-widget.js 의 규칙 기반 응답을 쓴다.

const CHATBOT_AI_DEFAULT_MODEL = 'claude-haiku-4-5';
const CHATBOT_AI_ENDPOINT = 'https://api.anthropic.com/v1/messages';
/** 사용자 메시지 1개 최대 글자 수 (위젯 입력창 maxlength 와 같음) */
const CHATBOT_AI_MAX_INPUT = 200;
/** API 로 보내는 최근 대화 수 (사용자·답변 합계) */
const CHATBOT_AI_HISTORY = 10;
/** 세션당 1시간 최대 질문 수 (공개 엔드포인트 비용 보호) */
const CHATBOT_AI_HOURLY_LIMIT = 30;

/** 말투 값 => 시스템 프롬프트 지시 (관리자 화면 말투 예시와 같은 어미) */
const CHATBOT_AI_TONE_PROMPTS = [
    'friendly' => '밝고 친근하게, "~알려드릴게요" 같은 어미로 답하고 이모지는 가끔 하나만 쓴다.',
    'calm' => '차분하고 신뢰감 있게, "~안내드리겠습니다." 같은 어미로 답한다. 이모지는 쓰지 않는다.',
    'warm' => '따뜻하게 공감하며, "~같이 살펴볼까요?" 같은 어미로 답한다.',
    'concise' => '간결하고 사실적으로, "~안내드립니다." 같은 어미로 짧게 답한다. 이모지는 쓰지 않는다.',
];

/** @return array{api_key: string, model: string} 저장값, 없으면 키 없음 + 기본 모델 */
function chatbot_ai(): array
{
    $row = db()->query('SELECT api_key, model FROM chatbot_ai WHERE id = 1')->fetch();
    return $row ?: ['api_key' => '', 'model' => CHATBOT_AI_DEFAULT_MODEL];
}

/** 관리자 화면용 상태. 키 원문은 브라우저로 보내지 않고 앞·뒤 일부만 보인다 */
function chatbot_ai_public(): array
{
    $ai = chatbot_ai();
    $key = $ai['api_key'];
    return [
        'configured' => $key !== '',
        'maskedKey' => $key === '' ? '' : substr($key, 0, 10) . '…' . substr($key, -4),
        'model' => $ai['model'],
    ];
}

/**
 * API 키·모델 저장. $apiKey 가 '' 이면 기존 키 유지. 문제가 있으면 RuntimeException(사용자용 메시지).
 */
function chatbot_ai_save(string $apiKey, string $model): void
{
    $apiKey = trim($apiKey);
    $model = trim($model) ?: CHATBOT_AI_DEFAULT_MODEL;
    if ($apiKey !== '' && (strlen($apiKey) > 255 || preg_match('/^[\x21-\x7e]+$/', $apiKey) !== 1)) {
        throw new RuntimeException('API 키 형식이 올바르지 않습니다.');
    }
    if (preg_match('/^[a-z0-9][a-z0-9.\-]{0,99}$/', $model) !== 1) {
        throw new RuntimeException('모델 이름은 claude-haiku-4-5 처럼 입력해 주세요.');
    }
    if ($apiKey === '') {
        $apiKey = chatbot_ai()['api_key'];
    }
    db()->prepare(
        'INSERT INTO chatbot_ai (id, api_key, model) VALUES (1, ?, ?)
         ON DUPLICATE KEY UPDATE api_key = VALUES(api_key), model = VALUES(model)'
    )->execute([$apiKey, $model]);
}

/** API 키만 삭제 (모델은 유지) → 규칙 기반 응답으로 돌아간다 */
function chatbot_ai_remove_key(): void
{
    db()->prepare('UPDATE chatbot_ai SET api_key = ? WHERE id = 1')->execute(['']);
}

/** 챗봇 설정(이름·말투)과 회사 정보로 만든 시스템 프롬프트 */
function chatbot_ai_system_prompt(array $settings): string
{
    $contact = site_contact();
    $tone = CHATBOT_AI_TONE_PROMPTS[$settings['tone']] ?? CHATBOT_AI_TONE_PROMPTS['friendly'];
    return <<<PROMPT
        너는 디지털 에이전시 fuse.lab 웹사이트의 상담 챗봇 "{$settings['ai_name']}" 이다.

        [회사 정보]
        - 서비스: 웹서비스·MVP, 브랜드 웹사이트, 자동화·내부 도구, UX·UI 디자인, 콘텐츠·커뮤니티, 디지털 경험
        - 작업 사례: 사이트의 포트폴리오 페이지(/portfolio.php)
        - 견적: 프로젝트 범위·일정에 따라 다르며, 메인 페이지 하단 견적 문의 폼으로 접수하면 담당자가 안내
        - 이메일 {$contact['email']} / 전화 {$contact['phone']} / 주소 {$contact['address']}

        [답변 규칙]
        - 한국어로, 3~4문장 이내로 답한다. 마크다운·목록 기호 없이 일반 텍스트로만 쓴다.
        - 말투: {$tone}
        - 위 정보에 없는 가격·일정·경력·고객사 등은 지어내지 말고 견적 문의 폼이나 연락처로 안내한다.
        - fuse.lab 과 관계없는 요청(코드 작성, 일반 상식 등)은 정중히 거절하고 프로젝트 상담으로 안내한다.
        PROMPT;
}

/**
 * 대화 기록 검증. 사용자 → 답변이 번갈아 오고 마지막은 사용자 질문이어야 한다.
 * @param mixed $messages JSON 디코드한 값
 * @return list<array{role: string, content: string}> 최근 CHATBOT_AI_HISTORY 개 (사용자 메시지로 시작)
 */
function chatbot_ai_messages(mixed $messages): array
{
    if (!is_array($messages) || $messages === [] || !array_is_list($messages)) {
        throw new RuntimeException('질문을 입력해 주세요.');
    }
    $clean = [];
    foreach ($messages as $index => $message) {
        $role = $index % 2 === 0 ? 'user' : 'assistant';
        if (!is_array($message) || ($message['role'] ?? null) !== $role || !is_string($message['content'] ?? null)) {
            throw new RuntimeException('대화 형식이 올바르지 않습니다.');
        }
        $content = trim($message['content']);
        // 답변은 모델이 만든 글이라 길 수 있어 넉넉히 자른다
        $max = $role === 'user' ? CHATBOT_AI_MAX_INPUT : 2000;
        if ($content === '' || mb_strlen($content) > $max) {
            throw new RuntimeException('질문은 ' . CHATBOT_AI_MAX_INPUT . '자 이내로 입력해 주세요.');
        }
        $clean[] = ['role' => $role, 'content' => $content];
    }
    if (end($clean)['role'] !== 'user') {
        throw new RuntimeException('대화 형식이 올바르지 않습니다.');
    }
    // 홀수 개(사용자로 시작·끝)를 유지하며 최근 것만
    $keep = CHATBOT_AI_HISTORY - (CHATBOT_AI_HISTORY % 2 === 0 ? 1 : 0);
    return array_slice($clean, -min($keep, count($clean)));
}

/** 세션당 1시간 질문 수 제한. 넘으면 false */
function chatbot_ai_allow(): bool
{
    $now = time();
    $usage = $_SESSION['chatbot_ai'] ?? ['since' => $now, 'count' => 0];
    if ($now - $usage['since'] >= 3600) {
        $usage = ['since' => $now, 'count' => 0];
    }
    if ($usage['count'] >= CHATBOT_AI_HOURLY_LIMIT) {
        return false;
    }
    $usage['count']++;
    $_SESSION['chatbot_ai'] = $usage;
    return true;
}

/**
 * Claude 답변 텍스트. 키 없음·네트워크·API 오류는 RuntimeException (원인은 error_log 에 남긴다).
 * @param list<array{role: string, content: string}> $messages chatbot_ai_messages() 결과
 */
function chatbot_ai_reply(array $messages): string
{
    $ai = chatbot_ai();
    if ($ai['api_key'] === '') {
        throw new RuntimeException('AI 연동이 설정되지 않았습니다.');
    }
    $body = json_encode([
        'model' => $ai['model'],
        'max_tokens' => 600,
        'system' => chatbot_ai_system_prompt(chatbot_settings()),
        'messages' => $messages,
    ], JSON_UNESCAPED_UNICODE);

    $curl = curl_init(CHATBOT_AI_ENDPOINT);
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTPHEADER => [
            'content-type: application/json',
            'anthropic-version: 2023-06-01',
            'x-api-key: ' . $ai['api_key'],
        ],
    ]);
    $response = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if ($response === false) {
        error_log('chatbot_ai: ' . $error);
        throw new RuntimeException('AI 서버에 연결하지 못했습니다.');
    }
    $data = json_decode($response, true);
    if ($status !== 200 || !is_array($data)) {
        error_log('chatbot_ai: HTTP ' . $status . ' ' . mb_substr((string) $response, 0, 500));
        throw new RuntimeException('AI 응답에 실패했습니다.');
    }
    $text = '';
    foreach ($data['content'] ?? [] as $block) {
        if (($block['type'] ?? '') === 'text') {
            $text .= $block['text'];
        }
    }
    $text = trim($text);
    if ($text === '') {
        throw new RuntimeException('AI 응답이 비어 있습니다.');
    }
    return $text;
}
