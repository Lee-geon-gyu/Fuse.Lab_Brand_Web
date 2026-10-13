<?php
/**
 * 서브페이지 관리 목록 (app/subpages.php). POST + CSRF 후 PRG.
 *   action=create  slug, title → 만든 페이지 편집 화면으로
 *   action=delete  id (만든 페이지만)
 * 각 항목의 편집은 /admin/subpage-edit.php (HTML·CSS·JS, 기존 PHP 페이지는 slot() 문구도)
 */
require __DIR__ . '/../app/bootstrap.php';
require_admin();

$listUrl = '/admin/subpages.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        flash('subpages', '요청이 만료되었습니다. 다시 시도해 주세요.');
        redirect($listUrl);
    }
    $action = (string) ($_POST['action'] ?? '');
    try {
        if ($action === 'create') {
            $id = subpage_create((string) ($_POST['slug'] ?? ''), (string) ($_POST['title'] ?? ''));
            flash('subpage-edit', '페이지를 만들었습니다. 내용을 입력하고 노출을 켜면 사이트에 보입니다.');
            redirect('/admin/subpage-edit.php?id=' . $id);
        }
        if ($action === 'delete') {
            subpage_delete((int) ($_POST['id'] ?? 0));
            flash('subpages', '페이지를 삭제했습니다.');
        }
    } catch (RuntimeException $exception) {
        flash('subpages', $exception->getMessage());
    }
    redirect($listUrl);
}

$pages = subpage_list();
$message = flash('subpages');

view('admin/head', [
    'pageTitle' => '서브페이지 관리',
    'styles' => ['/css/admin-board.css', '/css/admin-portfolios.css', '/css/admin-boards.css', '/css/admin-subpages.css', '/css/admin-layout.css'],
    'scripts' => ['/js/admin-portfolios.js'],
]);
view('admin/layout-start', ['active' => 'subpages']);
?>
    <main class="board-shell">
      <header class="page-header">
        <h1>서브페이지 관리</h1>
        <div class="page-header__bottom">
          <p class="post-count">총 <strong><?= count($pages) ?></strong>개</p>
        </div>
      </header>

      <form id="delete-form" method="post" action="<?= $listUrl ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete" />
      </form>

      <section class="board-card" aria-label="서브페이지 목록">
        <div class="board-card__content">
          <form class="search-toolbar" method="post" action="<?= $listUrl ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create" />
            <div class="search-toolbar__controls board-create__controls">
              <span class="subpage-slug-prefix">/page/</span>
              <input class="board-input board-input--key" type="text" name="slug" maxlength="<?= SUBPAGE_SLUG_MAX ?>" pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="주소 (예: about)" aria-label="페이지 주소" title="영소문자·숫자·하이픈(-)" required />
              <input class="board-input board-input--name" type="text" name="title" maxlength="<?= SUBPAGE_TITLE_MAX ?>" placeholder="페이지 이름 (예: 회사 소개)" aria-label="페이지 이름" required />
              <button class="pill-button pill-button--primary" type="submit">새 서브페이지 만들기</button>
            </div>
          </form>

          <div class="table-scroll">
            <table class="post-table subpages-table">
              <colgroup>
                <col />
                <col class="col-url" />
                <col class="col-kind" />
                <col class="col-state" />
                <col class="col-manage" />
              </colgroup>
              <thead>
                <tr>
                  <th scope="col">이름</th>
                  <th scope="col">주소</th>
                  <th scope="col">종류</th>
                  <th scope="col">상태</th>
                  <th scope="col">관리</th>
                </tr>
              </thead>
              <tbody>
<?php foreach ($pages as $page): ?>
<?php
    $url = subpage_url($page);
    $isCustom = $page['kind'] === 'custom';
    $hasCode = trim($page['html'] . $page['css'] . $page['js']) !== '';
?>
                <tr>
                  <td class="is-ellipsis"><a class="subpage-name" href="<?= e(subpage_edit_url($page)) ?>" title="<?= e($page['title']) ?>"><?= e($page['title']) ?></a></td>
                  <td class="is-ellipsis"><a class="subpage-url" href="<?= e($url) ?>" target="_blank" rel="noopener" title="<?= e($url) ?> (새 창)"><?= e($url) ?></a></td>
                  <td><?= $isCustom ? '만든 페이지' : '기존 페이지' ?></td>
                  <td>
<?php if ($isCustom): ?>
                    <?= $page['is_visible'] ? '노출' : '<span class="is-hidden-label">숨김</span>' ?>
<?php else: ?>
                    <?= $page['slot_count'] ? "문구 {$page['slot_count']}" : '' ?><?= $page['slot_count'] && $hasCode ? ' · ' : '' ?><?= $hasCode ? '코드 추가됨' : '' ?><?= !$page['slot_count'] && !$hasCode ? '-' : '' ?>
<?php endif; ?>
                  </td>
                  <td>
                    <div class="board-manage">
                      <a class="pill-button pill-button--muted" href="<?= e(subpage_edit_url($page)) ?>">편집</a>
<?php if ($isCustom): ?>
                      <button class="pill-button pill-button--danger" type="submit" name="id" value="<?= (int) $page['id'] ?>" form="delete-form" data-confirm="<?= e($page['title']) ?> 페이지를 삭제할까요? 되돌릴 수 없습니다.">삭제</button>
<?php else: ?>
                      <span class="is-hidden-label" title="코드로 만든 페이지라 삭제할 수 없습니다">삭제 불가</span>
<?php endif; ?>
                    </div>
                  </td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="bulk-row">
            <span class="order-hint">기존 페이지는 본문 아래 HTML·CSS·JS 를 덧붙이고 문구를 바꿀 수 있습니다. 만든 페이지는 /page/주소 로 열리며, 노출을 켜야 방문자에게 보입니다.</span>
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
