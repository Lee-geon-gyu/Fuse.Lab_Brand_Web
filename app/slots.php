<?php
/**
 * 페이지 문구 슬롯. PHP 파일 어디서든
 *   <?= slot('heading', 'WEB EXPERIENCES CRAFTED') ?>            한 줄 텍스트 (이스케이프)
 *   <?= slot('intro', '', 'multiline') ?>                         여러 줄 텍스트 (이스케이프 + 줄바꿈)
 *   <?= slot('notice', '', 'html') ?>                             HTML (관리자 입력 그대로 출력)
 *   slot_value('page_title', '포트폴리오 목록')                     가공 전 값 (view 인자 등에 넘길 때)
 * 처럼 쓰면 관리자 > 서브페이지 관리에서 그 파일을 고를 때 슬롯이 자동으로 편집 칸이 된다.
 * 슬롯은 "호출한 파일(저장소 루트 기준 경로) + 키" 로 저장한다 (page_slots). 저장값이 없으면 기본값.
 * 관리 화면은 소스의 slot()/slot_value() 호출을 읽어 목록을 만들므로 키·기본값·유형은 문자열 그대로 적는다.
 */

const SLOT_TYPES = [
    'text' => ['한 줄', 500],
    'multiline' => ['여러 줄', 5000],
    'html' => ['HTML', 20000],
];
const SLOT_KEY_PATTERN = '/^[a-z0-9_.-]{1,60}$/';

/** 슬롯을 찾을 PHP 파일 (저장소 루트 기준 경로). 사용자 페이지와 사용자 공통 view */
function slot_files(): array
{
    $root = dirname(__DIR__);
    $files = array_merge(glob($root . '/*.php') ?: [], glob($root . '/app/views/site/*.php') ?: []);
    return array_map(static fn (string $path): string => substr($path, strlen($root) + 1), $files);
}

/** 호출한 파일의 저장된 슬롯 값 [키 => 값] (요청당 파일별 1회 조회) */
function slot_saved(string $file): array
{
    static $cache = [];
    if (!isset($cache[$file])) {
        $stmt = db()->prepare('SELECT slot_key, value FROM page_slots WHERE file = ?');
        $stmt->execute([$file]);
        $cache[$file] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $cache[$file];
}

/** slot()/slot_value() 를 부른 파일의 루트 기준 경로 */
function slot_caller_file(int $depth): string
{
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $depth + 1);
    $path = str_replace('\\', '/', $trace[$depth]['file'] ?? '');
    $root = str_replace('\\', '/', dirname(__DIR__)) . '/';
    return str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
}

/** 슬롯 원래 값 (저장값, 없으면 기본값). 출력할 때는 e() 등으로 직접 처리한다 */
function slot_value(string $key, string $default = ''): string
{
    $value = slot_saved(slot_caller_file(1))[$key] ?? '';
    return $value !== '' ? $value : $default;
}

/** 슬롯 출력용 HTML (유형별 이스케이프) */
function slot(string $key, string $default = '', string $type = 'text'): string
{
    $value = slot_saved(slot_caller_file(1))[$key] ?? '';
    $value = $value !== '' ? $value : $default;
    return match ($type) {
        'html' => $value,
        'multiline' => nl2br(e($value), false),
        default => e($value),
    };
}

/**
 * 파일 소스에서 slot()/slot_value() 호출을 찾는다 (인자가 문자열 리터럴인 것만).
 * @return array<string, array{default: string, type: string}> 키 => 기본값·유형 (같은 키는 처음 것)
 */
function slot_scan(string $file): array
{
    static $cache = [];
    return $cache[$file] ??= slot_scan_source($file);
}

function slot_scan_source(string $file): array
{
    $source = @file_get_contents(dirname(__DIR__) . '/' . $file);
    if ($source === false || (!str_contains($source, 'slot(') && !str_contains($source, 'slot_value('))) {
        return [];
    }
    $tokens = array_values(array_filter(
        token_get_all($source),
        static fn ($token): bool => !is_array($token) || !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)
    ));
    $slots = [];
    foreach ($tokens as $index => $token) {
        if (!is_array($token) || $token[0] !== T_STRING || !in_array($token[1], ['slot', 'slot_value'], true)) {
            continue;
        }
        // 함수 정의(function slot)·메서드 호출(->slot) 제외
        $previous = $tokens[$index - 1] ?? null;
        if (is_array($previous) && in_array($previous[0], [T_FUNCTION, T_OBJECT_OPERATOR, T_DOUBLE_COLON], true)) {
            continue;
        }
        if (($tokens[$index + 1] ?? null) !== '(') {
            continue;
        }
        $args = [];
        for ($i = $index + 2; isset($tokens[$i]) && count($args) < 3; $i++) {
            $arg = $tokens[$i];
            if (is_array($arg) && $arg[0] === T_CONSTANT_ENCAPSED_STRING) {
                $args[] = stripcslashes(substr($arg[1], 1, -1));
            } elseif ($arg === ',') {
                continue;
            } else {
                break; // ')' 또는 리터럴이 아닌 인자
            }
        }
        $key = $args[0] ?? '';
        if (!preg_match(SLOT_KEY_PATTERN, $key) || isset($slots[$key])) {
            continue;
        }
        $type = $token[1] === 'slot' ? ($args[2] ?? 'text') : 'text';
        $slots[$key] = ['default' => $args[1] ?? '', 'type' => isset(SLOT_TYPES[$type]) ? $type : 'text'];
    }
    return $slots;
}

/**
 * 파일의 슬롯 값 저장. 빈 값·기본값과 같은 값은 행을 지워 기본값으로 돌린다.
 * @throws RuntimeException 사용자에게 보여줄 메시지
 */
function slot_save(string $file, array $input): void
{
    if (!in_array($file, slot_files(), true)) {
        throw new RuntimeException('페이지 파일을 찾을 수 없습니다.');
    }
    $values = [];
    foreach (slot_scan($file) as $key => ['default' => $default, 'type' => $type]) {
        $value = str_replace("\r\n", "\n", (string) ($input[$key] ?? ''));
        $value = $type === 'text' ? trim($value) : rtrim($value);
        $max = SLOT_TYPES[$type][1];
        if (mb_strlen($value) > $max) {
            throw new RuntimeException("{$key} 는 {$max}자 이내로 입력해 주세요.");
        }
        $values[$key] = $value === $default ? '' : $value;
    }

    $pdo = db();
    $upsert = $pdo->prepare(
        'INSERT INTO page_slots (file, slot_key, value) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE value = VALUES(value)'
    );
    $delete = $pdo->prepare('DELETE FROM page_slots WHERE file = ? AND slot_key = ?');
    $pdo->beginTransaction();
    foreach ($values as $key => $value) {
        if ($value === '') {
            $delete->execute([$file, $key]);
        } else {
            $upsert->execute([$file, $key, $value]);
        }
    }
    $pdo->commit();
}
