<?php
/**
 * 방문자·챗봇 사용 일별 집계 (visit_stats) + 관리자 대시보드 집계 쿼리.
 * 방문·챗봇 사용은 같은 세션을 하루 한 번만 센다.
 */

/** 사용자 페이지에서 호출: 오늘 방문자 수 +1 (세션당 하루 1회) */
function track_visit(): void
{
    stats_count_once('visitors');
}

/** 사용자 챗봇을 처음 열 때 호출: 오늘 챗봇 사용 세션 +1 (세션당 하루 1회) */
function track_chatbot_use(): void
{
    stats_count_once('chatbot_users');
}

function stats_count_once(string $column): void
{
    $today = date('Y-m-d');
    if (($_SESSION['stats'][$column] ?? null) === $today) {
        return;
    }
    $_SESSION['stats'][$column] = $today;

    // 컬럼명은 위 두 함수의 고정값만 들어옴
    $sql = match ($column) {
        'visitors' => 'INSERT INTO visit_stats (visit_date, visitors) VALUES (?, 1)
                       ON DUPLICATE KEY UPDATE visitors = visitors + 1',
        'chatbot_users' => 'INSERT INTO visit_stats (visit_date, chatbot_users) VALUES (?, 1)
                            ON DUPLICATE KEY UPDATE chatbot_users = chatbot_users + 1',
    };
    db()->prepare($sql)->execute([$today]);
}

/** 'YYYY-MM' 문자열을 해당 월 1일로 해석 (형식이 틀리면 null) */
function dashboard_parse_month(string $month): ?DateTimeImmutable
{
    if (!preg_match('/^(\d{4})-(\d{2})$/', $month, $m) || !checkdate((int) $m[2], 1, (int) $m[1])) {
        return null;
    }
    return new DateTimeImmutable($month . '-01');
}

/**
 * 선택 월의 방문자·챗봇 집계
 * @return array{month: string, daily: int[], visitorTotal: int, chatbotUsers: int, chatbotRate: float}
 *   daily = 1일부터 말일까지 일별 방문자 수, chatbotRate = 챗봇 사용 수 / 방문자 수 (%, 소수 1자리)
 */
function dashboard_month_stats(DateTimeImmutable $monthStart): array
{
    $days = (int) $monthStart->format('t');
    $stmt = db()->prepare(
        'SELECT DAY(visit_date) AS d, visitors, chatbot_users
         FROM visit_stats WHERE visit_date >= ? AND visit_date < ?'
    );
    $stmt->execute([$monthStart->format('Y-m-d'), $monthStart->modify('+1 month')->format('Y-m-d')]);

    $daily = array_fill(0, $days, 0);
    $chatbotUsers = 0;
    foreach ($stmt->fetchAll() as $row) {
        $daily[(int) $row['d'] - 1] = (int) $row['visitors'];
        $chatbotUsers += (int) $row['chatbot_users'];
    }
    $visitorTotal = array_sum($daily);

    return [
        'month' => $monthStart->format('Y-m'),
        'daily' => $daily,
        'visitorTotal' => $visitorTotal,
        'chatbotUsers' => $chatbotUsers,
        'chatbotRate' => $visitorTotal > 0 ? round($chatbotUsers / $visitorTotal * 100, 1) : 0.0,
    ];
}

/**
 * 관리자 계정 현황 (사용자 회원 기능이 없어 admins 기준)
 * @return array{total: int, month: int, today: int, activeRate: float}
 *   month = 선택 월 가입 수, today = 오늘 가입 수, activeRate = 최근 30일 안에 로그인한 계정 비율 (%)
 */
function dashboard_admin_stats(DateTimeImmutable $monthStart): array
{
    $today = new DateTimeImmutable('today');
    $stmt = db()->prepare(
        'SELECT COUNT(*) AS total,
                COALESCE(SUM(created_at >= ? AND created_at < ?), 0) AS month_count,
                COALESCE(SUM(created_at >= ?), 0) AS today_count,
                COALESCE(SUM(last_login_at >= ?), 0) AS active_count
         FROM admins'
    );
    $stmt->execute([
        $monthStart->format('Y-m-d'),
        $monthStart->modify('+1 month')->format('Y-m-d'),
        $today->format('Y-m-d'),
        (new DateTimeImmutable())->modify('-30 days')->format('Y-m-d H:i:s'),
    ]);
    $row = $stmt->fetch();
    $total = (int) $row['total'];

    return [
        'total' => $total,
        'month' => (int) $row['month_count'],
        'today' => (int) $row['today_count'],
        'activeRate' => $total > 0 ? round((int) $row['active_count'] / $total * 100, 1) : 0.0,
    ];
}

/** 최근 견적문의 (최신순) */
function dashboard_recent_inquiries(int $limit = 50): array
{
    $stmt = db()->prepare(
        'SELECT id, name, company, details, status, created_at
         FROM inquiries ORDER BY created_at DESC, id DESC LIMIT ?'
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return array_map(static fn (array $row): array => [
        'id' => (int) $row['id'],
        'author' => $row['name'],
        'company' => $row['company'],
        'content' => $row['details'],
        'date' => date('Y. m. d', strtotime($row['created_at'])),
        'status' => INQUIRY_STATUSES[$row['status']] ?? $row['status'],
    ], $stmt->fetchAll());
}

/** 최근 게시글 (최신순, 전 게시판) */
function dashboard_recent_posts(int $limit = 100): array
{
    $stmt = db()->prepare(
        'SELECT p.id, p.board, p.title, p.is_visible, p.views, p.created_at, a.name AS author
         FROM posts p LEFT JOIN admins a ON a.id = p.admin_id
         ORDER BY p.id DESC LIMIT ?'
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return array_map(static fn (array $row): array => [
        'id' => (int) $row['id'],
        'board' => $row['board'],
        'category' => POST_BOARDS[$row['board']] ?? $row['board'],
        'title' => $row['title'],
        'author' => $row['author'] ?? '(삭제된 관리자)',
        'date' => date('m-d', strtotime($row['created_at'])),
        'views' => (int) $row['views'],
        'visible' => (bool) $row['is_visible'],
    ], $stmt->fetchAll());
}
