<?php
/**
 * 견적문의 (inquiries) 공통 함수.
 *   사용자: inquiry_validate() → inquiry_create()  (/inquiry.php)
 *   관리자: inquiry_search() / inquiry_find() / inquiry_update() / inquiry_delete()  (/admin/inquiries.php)
 *   대시보드: inquiry_recent() / inquiry_count()
 */

const INQUIRY_STATUSES = [
    'received' => '접수 완료',
    'reviewing' => '검토 중',
    'waiting' => '답변 대기',
    'done' => '처리 완료',
    'rejected' => '반려',
];

/** 같은 세션에서 다시 문의할 수 있기까지의 최소 간격(초) */
const INQUIRY_SUBMIT_INTERVAL = 30;

/** 입력 항목별 최대 글자 수 (db/schema.sql 컬럼 길이 기준, details 는 TEXT 이므로 적당히 제한) */
const INQUIRY_MAX_LENGTHS = [
    'name' => 50,
    'email' => 255,
    'phone' => 30,
    'budget' => 100,
    'schedule' => 100,
    'company' => 100,
    'details' => 5000,
    'admin_memo' => 5000,
];

function inquiry_status_label(string $status): string
{
    return INQUIRY_STATUSES[$status] ?? $status;
}

/**
 * 사용자 폼 입력 검증.
 * @return array{0: array<string, string>, 1: array<string, string>} [정리된 값, 항목별 오류 메시지]
 */
function inquiry_validate(array $input): array
{
    $data = [];
    foreach (['name', 'email', 'phone', 'budget', 'schedule', 'company', 'details'] as $field) {
        $value = $input[$field] ?? '';
        $value = is_string($value) ? trim(str_replace("\r\n", "\n", $value)) : '';
        $data[$field] = $value;
    }

    $errors = [];
    $required = [
        'name' => '이름을 입력해 주세요.',
        'email' => '이메일을 입력해 주세요.',
        'phone' => '연락처를 입력해 주세요.',
        'details' => '프로젝트 내용을 입력해 주세요.',
    ];
    foreach ($required as $field => $message) {
        if ($data[$field] === '') {
            $errors[$field] = $message;
        }
    }

    if (!isset($errors['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = '이메일 형식이 올바르지 않습니다.';
    }
    if (!isset($errors['phone']) && !preg_match('/^[0-9+\-() ]{7,30}$/', $data['phone'])) {
        $errors['phone'] = '연락처는 숫자와 - + ( ) 만 사용해 7자 이상 입력해 주세요.';
    }

    foreach ($data as $field => $value) {
        if (!isset($errors[$field]) && mb_strlen($value) > INQUIRY_MAX_LENGTHS[$field]) {
            $errors[$field] = INQUIRY_MAX_LENGTHS[$field] . '자 이내로 입력해 주세요.';
        }
    }

    if (empty($input['privacy'])) {
        $errors['privacy'] = '개인정보 수집·이용에 동의해 주세요.';
    }

    return [$data, $errors];
}

/** 검증된 문의 저장. 개인정보 동의 시각은 저장 시각으로 기록 */
function inquiry_create(array $data, string $ip): int
{
    $stmt = db()->prepare(
        'INSERT INTO inquiries (name, email, phone, budget, schedule, company, details, status, privacy_agreed_at, ip)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)'
    );
    $stmt->execute([
        $data['name'], $data['email'], $data['phone'], $data['budget'],
        $data['schedule'], $data['company'], $data['details'], 'received', mb_substr($ip, 0, 45),
    ]);
    return (int) db()->lastInsertId();
}

/** 상태·검색어 조건 (WHERE 절과 바인딩 값) */
function inquiry_search_where(string $status, string $query): array
{
    $where = [];
    $params = [];
    if (isset(INQUIRY_STATUSES[$status])) {
        $where[] = 'status = ?';
        $params[] = $status;
    }
    if ($query !== '') {
        $like = '%' . addcslashes($query, '\\%_') . '%';
        $where[] = '(name LIKE ? OR company LIKE ? OR details LIKE ?)';
        array_push($params, $like, $like, $like);
    }
    return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
}

/** 관리자 목록: 상태 필터 + 이름/회사/내용 검색, 최신순 */
function inquiry_search(string $status, string $query, int $limit, int $offset): array
{
    [$where, $params] = inquiry_search_where($status, $query);
    $stmt = db()->prepare(
        "SELECT id, name, email, phone, company, details, status, created_at
         FROM inquiries {$where} ORDER BY id DESC LIMIT ? OFFSET ?"
    );
    $stmt->execute([...$params, max(1, $limit), max(0, $offset)]);
    return $stmt->fetchAll();
}

function inquiry_search_count(string $status, string $query): int
{
    [$where, $params] = inquiry_search_where($status, $query);
    $stmt = db()->prepare("SELECT COUNT(*) FROM inquiries {$where}");
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/** 상태별 건수 (없는 상태는 0) */
function inquiry_status_counts(): array
{
    $counts = array_fill_keys(array_keys(INQUIRY_STATUSES), 0);
    $rows = db()->query('SELECT status, COUNT(*) AS cnt FROM inquiries GROUP BY status')->fetchAll();
    foreach ($rows as $row) {
        $counts[$row['status']] = (int) $row['cnt'];
    }
    return $counts;
}

/** 대시보드용: 최근 문의 (최신순) */
function inquiry_recent(int $limit, int $offset = 0): array
{
    $stmt = db()->prepare(
        'SELECT id, name, email, phone, company, details, status, created_at
         FROM inquiries ORDER BY id DESC LIMIT ? OFFSET ?'
    );
    $stmt->execute([max(1, $limit), max(0, $offset)]);
    return $stmt->fetchAll();
}

/** 대시보드용: 전체 문의 수 */
function inquiry_count(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM inquiries')->fetchColumn();
}

function inquiry_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM inquiries WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/** 관리자: 상태 + 메모 저장 (상태는 INQUIRY_STATUSES 키만 허용) */
function inquiry_update(int $id, string $status, string $memo): bool
{
    if (!isset(INQUIRY_STATUSES[$status])) {
        return false;
    }
    $memo = trim(str_replace("\r\n", "\n", $memo));
    $stmt = db()->prepare('UPDATE inquiries SET status = ?, admin_memo = ? WHERE id = ?');
    $stmt->execute([$status, $memo === '' ? null : mb_substr($memo, 0, INQUIRY_MAX_LENGTHS['admin_memo']), $id]);
    return inquiry_find($id) !== null;
}

function inquiry_delete(int $id): bool
{
    $stmt = db()->prepare('DELETE FROM inquiries WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->rowCount() > 0;
}
