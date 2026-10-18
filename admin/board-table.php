<?php
/**
 * 표 게시판 편집 (boards.type = 'table'). 열 추가·이름·순서·삭제, 행 추가·순서·삭제, 칸 입력을
 * js/admin-board-table.js 가 화면에서 처리하고, 저장은 표 전체를 JSON 으로 POST + CSRF (PRG).
 *   GET  ?board=키
 *   POST board=키, table = {"columns": ["열", ...], "rows": [["칸", ...], ...]}
 */
require __DIR__ . '/../app/bootstrap.php';
require_admin();

$boardKey = (string) ($_GET['board'] ?? $_POST['board'] ?? '');
$boards = board_list('table');
if (!isset($boards[$boardKey])) {
    flash('boards', '표 게시판을 찾을 수 없습니다.');
    redirect('/admin/boards.php');
}
$pageUrl = '/admin/board-table.php?board=' . rawurlencode($boardKey);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        flash('board-table', '요청이 만료되었습니다. 다시 시도해 주세요.');
        redirect($pageUrl);
    }
    try {
        board_table_save($boardKey, json_decode((string) ($_POST['table'] ?? ''), true));
        flash('board-table', '표를 저장했습니다.');
    } catch (RuntimeException $exception) {
        flash('board-table', $exception->getMessage());
    }
    redirect($pageUrl);
}

$boardName = $boards[$boardKey];
$table = board_table($boardKey);
$message = flash('board-table');

view('admin/head', [
    'pageTitle' => $boardName . ' 표 편집',
    'styles' => ['/css/admin-board.css', '/css/admin-portfolios.css', '/css/admin-board-table.css', '/css/admin-layout.css'],
    'scripts' => ['/js/admin-board-table.js'],
]);
view('admin/layout-start', ['active' => 'boards']);
?>
    <main class="board-shell">
      <header class="page-header">
        <h1 class="board-table__title" title="<?= e($boardName) ?>"><?= e($boardName) ?></h1>
        <div class="page-header__bottom">
          <p class="post-count">열 <strong id="column-count"><?= count($table['columns']) ?></strong>개 · 행 <strong id="row-count"><?= count($table['rows']) ?></strong>개</p>
          <div class="page-header__actions">
            <a class="pill-button pill-button--muted" href="/admin/boards.php">게시판 관리</a>
          </div>
        </div>
      </header>

      <section class="board-card" aria-label="<?= e($boardName) ?> 표">
        <form class="board-card__content" id="table-form" method="post" action="<?= e($pageUrl) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="board" value="<?= e($boardKey) ?>" />
          <input type="hidden" name="table" id="table-input" />

          <div class="search-toolbar table-toolbar">
            <div class="table-toolbar__group">
              <button class="pill-button pill-button--muted" type="button" id="add-column">열 추가</button>
              <button class="pill-button pill-button--muted" type="button" id="add-row">행 추가</button>
            </div>
            <button class="pill-button pill-button--primary" type="submit">저장</button>
          </div>

          <div class="table-scroll">
            <table class="post-table table-editor" id="table-editor"
              data-max-columns="<?= BOARD_TABLE_MAX_COLUMNS ?>"
              data-max-rows="<?= BOARD_TABLE_MAX_ROWS ?>"
              data-column-max="<?= BOARD_TABLE_COLUMN_MAX ?>"
              data-cell-max="<?= BOARD_TABLE_CELL_MAX ?>">
              <thead></thead>
              <tbody></tbody>
            </table>
          </div>
          <script type="application/json" id="table-data"><?= json_encode($table, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

          <div class="bulk-row">
            <span class="order-hint">열 이름은 비울 수 없습니다. 모든 칸이 빈 행은 저장할 때 빠집니다. 열 <?= BOARD_TABLE_MAX_COLUMNS ?>개 · 행 <?= number_format(BOARD_TABLE_MAX_ROWS) ?>개 · 칸 <?= number_format(BOARD_TABLE_CELL_MAX) ?>자까지.</span>
          </div>
        </form>
      </section>
    </main>

    <div class="toast" id="board-toast" role="status" aria-live="polite"<?= $message === null ? ' hidden' : ' data-auto-hide' ?>><?= e($message) ?></div>
<?php
view('admin/layout-end');
view('admin/foot');
