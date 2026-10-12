<?php
/**
 * 관리자 포트폴리오 목록. 변경은 모두 POST + CSRF 후 같은 화면으로 돌아온다 (PRG).
 *   action=bulk   ids[], op = show | hide | delete   (bulk-form)
 *   action=orders order[id] = 순서 번호               (order-form)
 *   action=move   move = "{id}:up" | "{id}:down"      (move-form)
 *   action=delete id                                  (편집 화면의 삭제 버튼)
 */
require __DIR__ . '/../app/bootstrap.php';
require_admin();

$category = (string) ($_GET['category'] ?? $_POST['category'] ?? '');
if (!isset(PORTFOLIO_CATEGORIES[$category])) {
    $category = '';
}
$listUrl = '/admin/portfolios.php' . ($category !== '' ? '?category=' . rawurlencode($category) : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        flash('portfolio', '요청이 만료되었습니다. 다시 시도해 주세요.');
        redirect($listUrl);
    }

    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'bulk') {
        $ids = (array) ($_POST['ids'] ?? []);
        $op = (string) ($_POST['op'] ?? '');
        if (!portfolio_clean_ids($ids)) {
            flash('portfolio', '먼저 포트폴리오를 선택해 주세요.');
        } elseif ($op === 'delete') {
            flash('portfolio', portfolio_delete($ids) . '건을 삭제했습니다.');
        } elseif ($op === 'show' || $op === 'hide') {
            portfolio_set_visible($ids, $op === 'show');
            flash('portfolio', count(portfolio_clean_ids($ids)) . '건을 ' . ($op === 'show' ? '노출로' : '숨김으로') . ' 바꿨습니다.');
        }
    } elseif ($action === 'orders') {
        $orders = [];
        foreach ((array) ($_POST['order'] ?? []) as $id => $order) {
            if ((int) $id > 0 && is_numeric($order)) {
                $orders[(int) $id] = max(-999999, min(999999, (int) $order));
            }
        }
        portfolio_set_orders($orders);
        flash('portfolio', '순서를 저장했습니다.');
    } elseif ($action === 'move') {
        [$id, $direction] = array_pad(explode(':', (string) ($_POST['move'] ?? ''), 2), 2, '');
        if (in_array($direction, ['up', 'down'], true) && portfolio_move((int) $id, $direction, $category)) {
            flash('portfolio', '순서를 바꿨습니다.');
        }
    } elseif ($action === 'delete') {
        flash('portfolio', portfolio_delete([(int) ($_POST['id'] ?? 0)]) ? '삭제했습니다.' : '포트폴리오를 찾을 수 없습니다.');
    }
    redirect($listUrl);
}

$rows = portfolio_admin_list($category);
$message = flash('portfolio');
$lastIndex = count($rows) - 1;

view('admin/head', [
    'pageTitle' => '포트폴리오 관리',
    'styles' => ['/css/admin-board.css', '/css/admin-portfolios.css', '/css/admin-layout.css'],
    'scripts' => ['/js/custom-select.js', '/js/admin-portfolios.js'],
]);
view('admin/layout-start', ['active' => 'portfolios']);
?>
    <main class="board-shell">
      <header class="page-header">
        <h1>포트폴리오 관리</h1>
        <div class="page-header__bottom">
          <p class="post-count">총 <strong><?= count($rows) ?></strong>건</p>
          <div class="page-header__actions">
            <form method="get" action="/admin/portfolios.php" data-auto-submit>
              <div class="select-box select-box--category" data-custom-select>
                <select name="category" aria-label="분류">
                  <option value="">전체</option>
<?php foreach (PORTFOLIO_CATEGORIES as $key => $label): ?>
                  <option value="<?= e($key) ?>"<?= $key === $category ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
                </select>
                <img src="/aseets/icons/admin/board-category-arrow.svg" alt="" width="20" height="20" />
              </div>
              <noscript><button class="pill-button pill-button--muted" type="submit">보기</button></noscript>
            </form>
            <a class="pill-button pill-button--primary" href="/admin/portfolio-edit.php">등록</a>
          </div>
        </div>
      </header>

      <form id="order-form" method="post" action="/admin/portfolios.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="orders" />
        <input type="hidden" name="category" value="<?= e($category) ?>" />
      </form>
      <form id="move-form" method="post" action="/admin/portfolios.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="move" />
        <input type="hidden" name="category" value="<?= e($category) ?>" />
      </form>

      <section class="board-card" aria-label="포트폴리오 목록">
        <form class="board-card__content" id="bulk-form" method="post" action="/admin/portfolios.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="bulk" />
          <input type="hidden" name="category" value="<?= e($category) ?>" />

          <div class="table-scroll">
            <table class="post-table portfolio-table">
              <colgroup>
                <col class="col-check" />
                <col class="col-thumb" />
                <col class="col-title" />
                <col class="col-category" />
                <col class="col-visible" />
                <col class="col-order" />
              </colgroup>
              <thead>
                <tr>
                  <th scope="col"><input type="checkbox" data-select-all aria-label="모두 선택" /></th>
                  <th scope="col">썸네일</th>
                  <th scope="col">제목</th>
                  <th scope="col">분류</th>
                  <th scope="col">노출</th>
                  <th scope="col">순서</th>
                </tr>
              </thead>
              <tbody>
<?php if (!$rows): ?>
                <tr class="empty-row"><td colspan="6">등록된 포트폴리오가 없습니다.</td></tr>
<?php endif; ?>
<?php foreach ($rows as $index => $row): ?>
                <tr>
                  <td><input class="row-check" type="checkbox" name="ids[]" value="<?= (int) $row['id'] ?>" aria-label="<?= e($row['title']) ?> 선택" /></td>
                  <td><img class="portfolio-thumb" src="<?= e($row['thumbnail']) ?>" alt="" width="64" height="64" loading="lazy" /></td>
                  <td><a class="post-title" href="/admin/portfolio-edit.php?id=<?= (int) $row['id'] ?>"><?= e($row['title']) ?></a></td>
                  <td><?= e(PORTFOLIO_CATEGORIES[$row['category']] ?? $row['category']) ?></td>
                  <td><?= $row['is_visible'] ? '노출' : '<span class="is-hidden-label">숨김</span>' ?></td>
                  <td>
                    <div class="order-controls">
                      <input class="order-input" type="number" name="order[<?= (int) $row['id'] ?>]" value="<?= (int) $row['sort_order'] ?>" form="order-form" aria-label="<?= e($row['title']) ?> 순서 번호" />
                      <button class="order-move" type="submit" name="move" value="<?= (int) $row['id'] ?>:up" form="move-form" aria-label="<?= e($row['title']) ?> 위로"<?= $index === 0 ? ' disabled' : '' ?>>▲</button>
                      <button class="order-move" type="submit" name="move" value="<?= (int) $row['id'] ?>:down" form="move-form" aria-label="<?= e($row['title']) ?> 아래로"<?= $index === $lastIndex ? ' disabled' : '' ?>>▼</button>
                    </div>
                  </td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="bulk-row">
            <div class="bulk-row__actions">
              <button class="pill-button pill-button--muted" type="submit" name="op" value="show">노출</button>
              <button class="pill-button pill-button--muted" type="submit" name="op" value="hide">숨김</button>
              <button class="pill-button pill-button--danger" type="submit" name="op" value="delete" data-confirm="선택한 포트폴리오를 삭제할까요? 업로드한 이미지도 함께 삭제됩니다.">삭제</button>
            </div>
            <div class="bulk-row__actions">
              <span class="order-hint">순서 번호가 작을수록 앞에 나옵니다</span>
              <button class="pill-button pill-button--muted" type="submit" form="order-form"<?= $rows ? '' : ' disabled' ?>>순서 저장</button>
            </div>
          </div>
        </form>
      </section>
    </main>

<?php if ($message !== null): ?>
    <div class="toast" id="board-toast" role="status" aria-live="polite" data-auto-hide><?= e($message) ?></div>
<?php endif; ?>
<?php
view('admin/layout-end');
view('admin/foot');
