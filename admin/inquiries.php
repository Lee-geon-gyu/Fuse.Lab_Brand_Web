<?php
require __DIR__ . '/../app/bootstrap.php';
require_admin();

const INQUIRIES_PER_PAGE = 10;

/** 목록 조건(상태·검색어·페이지)과 열린 문의 id 를 유지한 관리자 URL */
function inquiries_admin_url(string $status, string $query, int $page, ?int $id = null): string
{
    $params = array_filter([
        'status' => $status,
        'q' => $query,
        'page' => $page > 1 ? $page : null,
        'id' => $id,
    ], static fn($value) => $value !== null && $value !== '');
    return '/admin/inquiries.php' . ($params ? '?' . http_build_query($params) : '');
}

function inquiries_param(array $source, string $key): string
{
    $value = $source[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

// ---- 변경 요청 (POST + CSRF) 처리 후 목록으로 돌아감 ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $backStatus = inquiries_param($_POST, 'filter_status');
    $backQuery = inquiries_param($_POST, 'filter_q');
    $backPage = max(1, (int) inquiries_param($_POST, 'filter_page'));
    $id = (int) inquiries_param($_POST, 'id');
    $action = inquiries_param($_POST, 'action');

    if (!csrf_valid()) {
        flash('inquiries_admin', '요청이 만료되었습니다. 다시 시도해 주세요.');
        redirect(inquiries_admin_url($backStatus, $backQuery, $backPage, $id ?: null));
    }

    if ($action === 'update') {
        $saved = inquiry_update($id, inquiries_param($_POST, 'status'), (string) ($_POST['admin_memo'] ?? ''));
        flash('inquiries_admin', $saved ? '상태와 메모를 저장했습니다.' : '저장하지 못했습니다. 문의가 없거나 상태 값이 올바르지 않습니다.');
        redirect(inquiries_admin_url($backStatus, $backQuery, $backPage, $id));
    }

    if ($action === 'delete') {
        flash('inquiries_admin', inquiry_delete($id) ? '문의를 삭제했습니다.' : '이미 삭제된 문의입니다.');
        redirect(inquiries_admin_url($backStatus, $backQuery, $backPage));
    }

    flash('inquiries_admin', '알 수 없는 요청입니다.');
    redirect(inquiries_admin_url($backStatus, $backQuery, $backPage));
}

// ---- 목록 / 상세 ----
$status = inquiries_param($_GET, 'status');
if (!isset(INQUIRY_STATUSES[$status])) {
    $status = '';
}
$query = mb_substr(inquiries_param($_GET, 'q'), 0, 100);
$total = inquiry_search_count($status, $query);
$totalPages = max(1, (int) ceil($total / INQUIRIES_PER_PAGE));
$page = min($totalPages, max(1, (int) inquiries_param($_GET, 'page')));
$rows = inquiry_search($status, $query, INQUIRIES_PER_PAGE, ($page - 1) * INQUIRIES_PER_PAGE);
$statusCounts = inquiry_status_counts();

$detailId = (int) inquiries_param($_GET, 'id');
$detail = $detailId > 0 ? inquiry_find($detailId) : null;
$toast = flash('inquiries_admin') ?? '';
if ($detailId > 0 && $detail === null && $toast === '') {
    $toast = '해당 문의를 찾을 수 없습니다.';
}

// 페이지 번호 묶음 (10개 단위)
$groupStart = intdiv($page - 1, 10) * 10 + 1;
$groupEnd = min($totalPages, $groupStart + 9);

view('admin/head', [
    'pageTitle' => '견적문의',
    'styles' => ['/css/admin-board.css', '/css/admin-layout.css', '/css/admin-inquiries.css'],
    'scripts' => ['/js/custom-select.js', '/js/admin-inquiries.js'],
]);
view('admin/layout-start', ['active' => 'inquiries']);
?>
    <main class="board-shell inquiry-shell">
      <header class="page-header">
        <h1>견적문의</h1>
        <div class="page-header__bottom">
          <p class="post-count">총 <strong><?= $total ?></strong>건</p>
          <ul class="inquiry-status-summary" aria-label="상태별 문의 수">
<?php foreach (INQUIRY_STATUSES as $key => $label): ?>
            <li><span class="status-badge status-badge--<?= e($key) ?>"><?= e($label) ?></span> <?= $statusCounts[$key] ?></li>
<?php endforeach; ?>
          </ul>
        </div>
      </header>

<?php if ($detail !== null): ?>
      <section class="inquiry-detail" aria-labelledby="inquiry-detail-title">
        <div class="inquiry-detail__head">
          <h2 id="inquiry-detail-title"><?= e($detail['name']) ?> 님의 문의 <small>no.<?= (int) $detail['id'] ?></small></h2>
          <a class="pill-button pill-button--muted" href="<?= e(inquiries_admin_url($status, $query, $page)) ?>">닫기</a>
        </div>

        <dl class="inquiry-detail__fields">
          <div><dt>이름</dt><dd><?= e($detail['name']) ?></dd></div>
          <div><dt>이메일</dt><dd><a href="mailto:<?= e($detail['email']) ?>"><?= e($detail['email']) ?></a></dd></div>
          <div><dt>연락처</dt><dd><?= e($detail['phone']) ?></dd></div>
          <div><dt>회사</dt><dd><?= e($detail['company'] !== '' ? $detail['company'] : '-') ?></dd></div>
          <div><dt>예산</dt><dd><?= e($detail['budget'] !== '' ? $detail['budget'] : '-') ?></dd></div>
          <div><dt>일정</dt><dd><?= e($detail['schedule'] !== '' ? $detail['schedule'] : '-') ?></dd></div>
          <div><dt>접수일</dt><dd><?= e($detail['created_at']) ?></dd></div>
          <div><dt>개인정보 동의</dt><dd><?= e($detail['privacy_agreed_at']) ?></dd></div>
          <div><dt>최종 수정</dt><dd><?= e($detail['updated_at']) ?></dd></div>
          <div><dt>접수 IP</dt><dd><?= e($detail['ip'] !== '' ? $detail['ip'] : '-') ?></dd></div>
          <div class="inquiry-detail__wide"><dt>프로젝트 내용</dt><dd class="inquiry-detail__text"><?= e($detail['details']) ?></dd></div>
        </dl>

        <form class="inquiry-detail__form" method="post" action="/admin/inquiries.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="update" />
          <input type="hidden" name="id" value="<?= (int) $detail['id'] ?>" />
          <input type="hidden" name="filter_status" value="<?= e($status) ?>" />
          <input type="hidden" name="filter_q" value="<?= e($query) ?>" />
          <input type="hidden" name="filter_page" value="<?= $page ?>" />
          <div class="inquiry-detail__row">
            <label for="detail-status">상태</label>
            <div class="select-box" data-custom-select>
              <select id="detail-status" name="status" aria-label="처리 상태">
<?php foreach (INQUIRY_STATUSES as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $detail['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
              </select>
              <img src="/aseets/icons/admin/board-search-arrow.svg" alt="" width="20" height="20" />
            </div>
          </div>
          <div class="inquiry-detail__row inquiry-detail__row--memo">
            <label for="detail-memo">관리자 메모</label>
            <textarea id="detail-memo" name="admin_memo" maxlength="5000" rows="5" placeholder="내부 확인용 메모 (사용자에게 보이지 않음)"><?= e($detail['admin_memo'] ?? '') ?></textarea>
          </div>
          <div class="inquiry-detail__actions">
            <button class="pill-button pill-button--primary" type="submit">저장</button>
          </div>
        </form>

        <form class="inquiry-detail__delete" method="post" action="/admin/inquiries.php" data-confirm="이 문의를 삭제할까요? 삭제하면 되돌릴 수 없습니다.">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete" />
          <input type="hidden" name="id" value="<?= (int) $detail['id'] ?>" />
          <input type="hidden" name="filter_status" value="<?= e($status) ?>" />
          <input type="hidden" name="filter_q" value="<?= e($query) ?>" />
          <input type="hidden" name="filter_page" value="<?= $page ?>" />
          <button class="pill-button pill-button--danger" type="submit">삭제</button>
        </form>
      </section>
<?php endif; ?>

      <section class="board-card" aria-label="견적문의 목록">
        <div class="board-card__content">
          <form class="search-toolbar" id="inquiry-search" method="get" action="/admin/inquiries.php" role="search">
            <div class="search-toolbar__controls">
              <div class="select-box" data-custom-select>
                <select id="status-filter" name="status" aria-label="상태 필터" data-auto-submit>
                  <option value="">전체 상태</option>
<?php foreach (INQUIRY_STATUSES as $key => $label): ?>
                  <option value="<?= e($key) ?>"<?= $status === $key ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
                </select>
                <img src="/aseets/icons/admin/board-search-arrow.svg" alt="" width="20" height="20" />
              </div>
              <input id="search-query" type="search" name="q" value="<?= e($query) ?>" maxlength="100" placeholder="이름·회사·내용 검색" aria-label="검색어 입력" />
              <button class="pill-button pill-button--muted" type="submit">검색</button>
<?php if ($status !== '' || $query !== ''): ?>
              <a class="pill-button pill-button--muted" href="/admin/inquiries.php">초기화</a>
<?php endif; ?>
            </div>
          </form>

          <div class="table-scroll">
            <table class="post-table inquiry-table">
              <colgroup>
                <col class="col-number" />
                <col class="col-name" />
                <col class="col-company" />
                <col class="col-phone" />
                <col class="col-details" />
                <col class="col-status" />
                <col class="col-received" />
              </colgroup>
              <thead>
                <tr>
                  <th scope="col">no.</th>
                  <th scope="col">이름</th>
                  <th scope="col">회사</th>
                  <th scope="col">연락처</th>
                  <th scope="col">문의 내용</th>
                  <th scope="col">상태</th>
                  <th scope="col">접수일</th>
                </tr>
              </thead>
              <tbody>
<?php if (!$rows): ?>
                <tr class="empty-row"><td colspan="7"><?= $status !== '' || $query !== '' ? '조건에 맞는 문의가 없습니다.' : '접수된 문의가 없습니다.' ?></td></tr>
<?php endif; ?>
<?php foreach ($rows as $row): ?>
<?php $rowUrl = inquiries_admin_url($status, $query, $page, (int) $row['id']); ?>
                <tr<?= $detail !== null && (int) $detail['id'] === (int) $row['id'] ? ' class="is-selected" aria-current="true"' : '' ?>>
                  <td><?= (int) $row['id'] ?></td>
                  <td><a class="post-title" href="<?= e($rowUrl) ?>"><?= e($row['name']) ?></a></td>
                  <td class="inquiry-ellipsis"><?= e($row['company'] !== '' ? $row['company'] : '-') ?></td>
                  <td><?= e($row['phone']) ?></td>
                  <td class="inquiry-ellipsis"><a class="post-title" href="<?= e($rowUrl) ?>"><?= e(mb_substr(preg_replace('/\s+/u', ' ', $row['details']), 0, 80)) ?></a></td>
                  <td><span class="status-badge status-badge--<?= e($row['status']) ?>"><?= e(inquiry_status_label($row['status'])) ?></span></td>
                  <td><?= e(substr($row['created_at'], 0, 10)) ?></td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="bulk-row">
            <span></span>
            <span id="page-summary"><?= $page ?> / <?= $totalPages ?>페이지</span>
          </div>
        </div>

        <nav class="pagination inquiry-pagination" aria-label="견적문의 페이지 이동">
<?php if ($groupStart > 1): ?>
          <a class="pagination__group--previous" href="<?= e(inquiries_admin_url($status, $query, $groupStart - 1)) ?>" aria-label="이전 페이지 묶음">&lsaquo;</a>
<?php endif; ?>
<?php for ($p = $groupStart; $p <= $groupEnd; $p++): ?>
          <a href="<?= e(inquiries_admin_url($status, $query, $p)) ?>"<?= $p === $page ? ' aria-current="page"' : '' ?>><?= $p ?></a>
<?php endfor; ?>
<?php if ($groupEnd < $totalPages): ?>
          <a class="pagination__group--next" href="<?= e(inquiries_admin_url($status, $query, $groupEnd + 1)) ?>" aria-label="다음 페이지 묶음">&rsaquo;</a>
<?php endif; ?>
        </nav>
      </section>
    </main>

    <div class="toast" id="inquiry-toast" role="status" aria-live="polite"<?= $toast === '' ? ' hidden' : '' ?>><?= e($toast) ?></div>
<?php
view('admin/layout-end');
view('admin/foot');
