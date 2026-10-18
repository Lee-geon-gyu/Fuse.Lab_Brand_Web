<?php
/**
 * 포트폴리오 (portfolios) 공통 함수.
 * 이미지 업로드는 게시판과 같은 uploads/posts/ 를 쓴다 (post_store_upload / post_delete_upload 재사용).
 * 정렬: sort_order 오름차순, 같으면 id 오름차순.
 */

const PORTFOLIO_CATEGORIES = [
    'mvp' => 'MVP',
    'branding' => 'Branding',
    'uiux' => 'UI/UX',
    'front' => 'Front',
    'detail' => 'Detail',
];

/** 사용자 목록 한 번에 불러오는 개수 (3개씩 두 줄) */
const PORTFOLIO_PAGE_SIZE = 6;

/**
 * 사용자 목록: 노출 글을 $offset 부터 $limit 개.
 * @return array{items: array<int, array>, hasMore: bool}
 */
function portfolio_public_list(string $category, int $offset, int $limit): array
{
    $params = [];
    $where = 'is_visible = 1';
    if ($category !== '') {
        $where .= ' AND category = ?';
        $params[] = $category;
    }
    $stmt = db()->prepare(
        "SELECT id, title, thumbnail FROM portfolios WHERE $where
         ORDER BY sort_order, id LIMIT ? OFFSET ?"
    );
    foreach ($params as $index => $value) {
        $stmt->bindValue($index + 1, $value);
    }
    $stmt->bindValue(count($params) + 1, $limit + 1, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    return [
        'items' => array_slice($rows, 0, $limit),
        'hasMore' => count($rows) > $limit,
    ];
}

/** 1건 조회 (없으면 null). $visibleOnly 면 숨김 글도 null */
function portfolio_find(int $id, bool $visibleOnly = false): ?array
{
    $sql = 'SELECT * FROM portfolios WHERE id = ?' . ($visibleOnly ? ' AND is_visible = 1' : '');
    $stmt = db()->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/** 관리자 목록 ($category 가 '' 면 전체) */
function portfolio_admin_list(string $category): array
{
    if ($category === '') {
        return db()->query(
            'SELECT id, title, category, thumbnail, sort_order, is_visible, updated_at FROM portfolios ORDER BY sort_order, id'
        )->fetchAll();
    }
    $stmt = db()->prepare(
        'SELECT id, title, category, thumbnail, sort_order, is_visible, updated_at FROM portfolios
         WHERE category = ? ORDER BY sort_order, id'
    );
    $stmt->execute([$category]);
    return $stmt->fetchAll();
}

/** 새 글은 맨 뒤에 붙인다 */
function portfolio_next_order(): int
{
    return (int) db()->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM portfolios')->fetchColumn();
}

/**
 * 저장. $data 키: title, category, client, type, project_date, summary, site_url,
 * thumbnail, hero_image, detail_image, is_visible. 새 글이면 id 반환.
 */
function portfolio_save(?int $id, array $data): int
{
    $values = [
        $data['title'], $data['category'], $data['client'], $data['type'], $data['project_date'],
        $data['summary'], $data['site_url'], $data['thumbnail'], $data['hero_image'], $data['detail_image'],
        $data['is_visible'],
    ];
    if ($id !== null) {
        db()->prepare(
            'UPDATE portfolios SET title = ?, category = ?, client = ?, type = ?, project_date = ?, summary = ?,
             site_url = ?, thumbnail = ?, hero_image = ?, detail_image = ?, is_visible = ? WHERE id = ?'
        )->execute([...$values, $id]);
        return $id;
    }
    db()->prepare(
        'INSERT INTO portfolios (title, category, client, type, project_date, summary, site_url,
         thumbnail, hero_image, detail_image, is_visible, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([...$values, portfolio_next_order()]);
    return (int) db()->lastInsertId();
}

/** 노출/숨김 일괄 변경, 바뀐 행 수 */
function portfolio_set_visible(array $ids, bool $visible): int
{
    $ids = portfolio_clean_ids($ids);
    if (!$ids) {
        return 0;
    }
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("UPDATE portfolios SET is_visible = ? WHERE id IN ($marks)");
    $stmt->execute([$visible ? 1 : 0, ...$ids]);
    return $stmt->rowCount();
}

/** 삭제 + 업로드 이미지 정리, 삭제한 행 수 */
function portfolio_delete(array $ids): int
{
    $ids = portfolio_clean_ids($ids);
    if (!$ids) {
        return 0;
    }
    $marks = implode(',', array_fill(0, count($ids), '?'));

    $stmt = db()->prepare("SELECT thumbnail, hero_image, detail_image FROM portfolios WHERE id IN ($marks)");
    $stmt->execute($ids);
    $images = array_merge(...array_map('array_values', $stmt->fetchAll() ?: [[]]));

    $stmt = db()->prepare("DELETE FROM portfolios WHERE id IN ($marks)");
    $stmt->execute($ids);

    array_map('post_delete_upload', $images);
    return $stmt->rowCount();
}

/**
 * 순서 이동: $direction 'up' | 'down'. 이웃은 $category 목록('' 면 전체) 기준으로 찾고,
 * 전체 순서에서 두 글의 자리를 맞바꾼 뒤 sort_order 를 1부터 다시 매긴다.
 */
function portfolio_move(int $id, string $direction, string $category): bool
{
    $visibleIds = array_map('intval', array_column(portfolio_admin_list($category), 'id'));
    $index = array_search($id, $visibleIds, true);
    if ($index === false) {
        return false;
    }
    $neighborIndex = $direction === 'up' ? $index - 1 : $index + 1;
    if (!isset($visibleIds[$neighborIndex])) {
        return false;
    }
    $neighbor = $visibleIds[$neighborIndex];

    $allIds = array_map('intval', array_column(portfolio_admin_list(''), 'id'));
    $a = array_search($id, $allIds, true);
    $b = array_search($neighbor, $allIds, true);
    [$allIds[$a], $allIds[$b]] = [$allIds[$b], $allIds[$a]];

    portfolio_write_order($allIds);
    return true;
}

/** 순서 번호 직접 저장: [id => sort_order] */
function portfolio_set_orders(array $orders): void
{
    $stmt = db()->prepare('UPDATE portfolios SET sort_order = ? WHERE id = ?');
    $pdo = db();
    $pdo->beginTransaction();
    foreach ($orders as $id => $order) {
        $stmt->execute([(int) $order, (int) $id]);
    }
    $pdo->commit();
}

/** $ids 순서대로 sort_order = 1, 2, 3 ... */
function portfolio_write_order(array $ids): void
{
    $orders = [];
    foreach (array_values($ids) as $position => $id) {
        $orders[$id] = $position + 1;
    }
    portfolio_set_orders($orders);
}

function portfolio_clean_ids(array $ids): array
{
    return array_values(array_filter(array_unique(array_map('intval', $ids)), static fn (int $id): bool => $id > 0));
}
