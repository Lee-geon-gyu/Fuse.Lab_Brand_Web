<?php
/**
 * 관리자 게시판 관리 (boards 테이블). 변경은 모두 POST + CSRF 후 같은 화면으로 돌아온다 (PRG).
 *   action=create  board_key, name, type(post|table)     (create-form)
 *   action=rename  names[키] = 새 이름 (바뀐 것만 저장)   (rename-form, 행마다 저장 버튼·Enter 모두 같은 폼)
 *   action=move    move = "{키}:up" | "{키}:down"         (move-form)
 *   action=delete  key = 대상 키 (글·이미지·첨부 또는 표 데이터도 삭제)  (delete-form)
 * 표 게시판(type=table)의 열·행 편집은 /admin/board-table.php?board=키
 * portfolio 게시판은 메인 슬라이드용이라 삭제할 수 없다 (이름 변경·순서 변경은 가능).
 */
require __DIR__ . '/../app/bootstrap.php';
require_admin();

$listUrl = '/admin/boards.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        flash('boards', '요청이 만료되었습니다. 다시 시도해 주세요.');
        redirect($listUrl);
    }

    $action = (string) ($_POST['action'] ?? '');
    $key = (string) ($_POST['key'] ?? '');
    try {
        if ($action === 'create') {
            board_create((string) ($_POST['board_key'] ?? ''), (string) ($_POST['name'] ?? ''), (string) ($_POST['type'] ?? ''));
            flash('boards', '게시판을 만들었습니다.');
        } elseif ($action === 'rename') {
            $names = (array) ($_POST['names'] ?? []);
            $changed = 0;
            foreach (board_list() as $boardKey => $oldName) {
                if (isset($names[$boardKey]) && trim((string) $names[$boardKey]) !== $oldName) {
                    board_rename((string) $boardKey, (string) $names[$boardKey]);
                    $changed++;
                }
            }
            flash('boards', $changed ? '이름을 바꿨습니다.' : '바뀐 이름이 없습니다.');
        } elseif ($action === 'move') {
            [$moveKey, $direction] = array_pad(explode(':', (string) ($_POST['move'] ?? ''), 2), 2, '');
            if (in_array($direction, ['up', 'down'], true) && board_move($moveKey, $direction)) {
                flash('boards', '순서를 바꿨습니다.');
            }
        } elseif ($action === 'delete') {
            $deleted = board_delete($key);
            flash('boards', '게시판을 삭제했습니다.' . ($deleted ? " (글·행 {$deleted}건 함께 삭제)" : ''));
        }
    } catch (RuntimeException $exception) {
        flash('boards', $exception->getMessage());
    }
    redirect($listUrl);
}

$rows = board_admin_list();
$message = flash('boards');
$lastIndex = count($rows) - 1;

view('admin/head', [
    'pageTitle' => '게시판 관리',
    'styles' => ['/css/admin-board.css', '/css/admin-portfolios.css', '/css/admin-boards.css', '/css/admin-layout.css'],
    'scripts' => ['/js/admin-portfolios.js'],
]);
view('admin/layout-start', ['active' => 'boards']);
?>
    <main class="board-shell">
      <header class="page-header">
        <h1>게시판 관리</h1>
        <div class="page-header__bottom">
          <p class="post-count">총 <strong><?= count($rows) ?></strong>개</p>
          <div class="page-header__actions">
            <a class="pill-button pill-button--muted" href="/admin/board.php">게시글 목록</a>
          </div>
        </div>
      </header>

      <form id="rename-form" method="post" action="/admin/boards.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="rename" />
      </form>
      <form id="move-form" method="post" action="/admin/boards.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="move" />
      </form>
      <form id="delete-form" method="post" action="/admin/boards.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete" />
      </form>

      <section class="board-card" aria-label="게시판 목록">
        <div class="board-card__content">
          <form class="search-toolbar" id="create-form" method="post" action="/admin/boards.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create" />
            <div class="search-toolbar__controls board-create__controls">
              <input class="board-input board-input--key" type="text" name="board_key" maxlength="<?= BOARD_KEY_MAX ?>" pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="키 (예: notice)" aria-label="게시판 키" title="영소문자·숫자·하이픈(-), 만든 뒤 변경 불가" required />
              <input class="board-input board-input--name" type="text" name="name" maxlength="<?= BOARD_NAME_MAX ?>" placeholder="게시판 이름 (예: 공지사항)" aria-label="게시판 이름" required />
              <select class="board-input board-input--type" name="type" aria-label="게시판 유형">
<?php foreach (BOARD_TYPES as $type => $typeLabel): ?>
                <option value="<?= e($type) ?>"><?= e($typeLabel) ?></option>
<?php endforeach; ?>
              </select>
              <button class="pill-button pill-button--primary" type="submit">게시판 추가</button>
            </div>
          </form>

          <div class="table-scroll">
            <table class="post-table boards-table">
              <colgroup>
                <col class="col-order" />
                <col class="col-key" />
                <col />
                <col class="col-type" />
                <col class="col-count" />
                <col class="col-manage" />
              </colgroup>
              <thead>
                <tr>
                  <th scope="col">순서</th>
                  <th scope="col">키</th>
                  <th scope="col">이름</th>
                  <th scope="col">유형</th>
                  <th scope="col">글·행 수</th>
                  <th scope="col">관리</th>
                </tr>
              </thead>
              <tbody>
<?php if (!$rows): ?>
                <tr class="empty-row"><td colspan="6">등록된 게시판이 없습니다.</td></tr>
<?php endif; ?>
<?php foreach ($rows as $index => $row): ?>
<?php
    $boardKey = $row['board_key'];
    $postCount = (int) $row['post_count'];
    $isFixed = in_array($boardKey, [BOARD_PORTFOLIO, BOARD_PORTFOLIO_SUB], true);
    $isTable = $row['type'] === 'table';
    $countLabel = $isTable ? "행 {$postCount}개와" : "글 {$postCount}건과 이미지·첨부파일도";
?>
                <tr>
                  <td>
                    <div class="order-controls">
                      <button class="order-move" type="submit" name="move" value="<?= e($boardKey) ?>:up" form="move-form" aria-label="<?= e($row['name']) ?> 위로"<?= $index === 0 ? ' disabled' : '' ?>>▲</button>
                      <button class="order-move" type="submit" name="move" value="<?= e($boardKey) ?>:down" form="move-form" aria-label="<?= e($row['name']) ?> 아래로"<?= $index === $lastIndex ? ' disabled' : '' ?>>▼</button>
                    </div>
                  </td>
                  <td class="board-key" title="<?= e($boardKey) ?>"><?= e($boardKey) ?></td>
                  <td>
                    <div class="board-rename">
                      <input class="board-input" type="text" name="names[<?= e($boardKey) ?>]" value="<?= e($row['name']) ?>" maxlength="<?= BOARD_NAME_MAX ?>" form="rename-form" aria-label="<?= e($boardKey) ?> 게시판 이름" />
                      <button class="pill-button pill-button--muted" type="submit" form="rename-form">이름 저장</button>
                    </div>
                  </td>
                  <td><?= e(BOARD_TYPE_LABELS[$row['type']] ?? $row['type']) ?></td>
                  <td><?= number_format($postCount) ?></td>
                  <td>
                    <div class="board-manage">
                      <a class="pill-button pill-button--muted" href="<?= e(board_admin_url($boardKey, $row['type'])) ?>"><?= $isTable ? '표 편집' : '글 보기' ?></a>
<?php if ($isFixed): ?>
                      <span class="is-hidden-label" title="메인 슬라이드·포트폴리오 페이지에 쓰여 삭제할 수 없습니다">삭제 불가</span>
<?php else: ?>
                      <button class="pill-button pill-button--danger" type="submit" name="key" value="<?= e($boardKey) ?>" form="delete-form" data-confirm="<?= e($row['name']) ?> 게시판을 삭제할까요?<?= $postCount ? " {$countLabel} 함께 삭제되며 되돌릴 수 없습니다." : '' ?>">삭제</button>
<?php endif; ?>
                    </div>
                  </td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="bulk-row">
            <span class="order-hint">키는 글 저장용 식별자라 만든 뒤 바꿀 수 없습니다. 포트폴리오-main(메인 슬라이드)·포트폴리오-sub(포트폴리오 목록·상세) 게시판은 삭제할 수 없습니다. 게시판 이름은 겹칠 수 없습니다.</span>
          </div>
        </div>
      </section>
    </main>

<?php if ($message !== null): ?>
    <div class="toast" id="board-toast" role="status" aria-live="polite" data-auto-hide><?= e($message) ?></div>
<?php endif; ?>
<?php
view('admin/layout-end');
view('admin/foot');
