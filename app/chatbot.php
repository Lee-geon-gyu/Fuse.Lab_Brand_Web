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
