<?php
require __DIR__ . '/../app/bootstrap.php';
require_admin();

$monthStart = new DateTimeImmutable('first day of this month midnight');
$monthName = $monthStart->format('n') . '월';
$stats = dashboard_month_stats($monthStart);
$members = dashboard_admin_stats($monthStart);

view('admin/head', [
    'pageTitle' => '대시보드',
    'styles' => ['/css/admin-dashboard.css', '/css/admin-layout.css'],
    'scripts' => ['/js/admin-dashboard.js'],
    'bodyData' => [
        'dashboard-api' => '/admin/dashboard-api.php',
        'inquiry-url' => '/admin/inquiries.php',
        'post-edit-url' => '/admin/post-edit.php',
        'today' => date('Y-m-d'),
    ],
]);
view('admin/layout-start', ['active' => 'dashboard']);
?>
    <main class="dash-shell">
      <header class="dash-header">
        <div class="dash-header__title">
          <h1>대시보드</h1>
          <p>fuse.lab의 현황</p>
        </div>
        <div class="month-nav">
          <button class="month-nav__step month-nav__step--prev" id="month-prev" type="button" aria-label="이전 달">
            <img src="/aseets/icons/admin/dashboard/arrow-20.svg" alt="" width="20" height="20" />
          </button>
          <div class="month-nav__picker">
            <button class="month-nav__trigger" id="month-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-controls="month-panel" aria-label="조회 월 선택">
              <img src="/aseets/icons/admin/dashboard/calendar-month.svg" alt="" width="24" height="24" />
              <span id="month-label"><?= e($monthStart->format('Y. m')) ?></span>
              <img class="month-nav__caret" src="/aseets/icons/admin/dashboard/arrow-down-12.svg" alt="" width="12" height="12" />
            </button>
            <input id="month-input" type="hidden" value="<?= e($monthStart->format('Y-m')) ?>" />
            <div class="month-panel" id="month-panel" role="dialog" aria-label="조회 월 선택" hidden>
              <div class="month-panel__head">
                <button class="month-panel__year-step month-panel__year-step--prev" type="button" data-year-step="-1" aria-label="이전 해">
                  <img src="/aseets/icons/admin/dashboard/arrow-20.svg" alt="" width="20" height="20" />
                </button>
                <strong class="month-panel__year" id="month-panel-year" aria-live="polite"></strong>
                <button class="month-panel__year-step" type="button" data-year-step="1" aria-label="다음 해">
                  <img src="/aseets/icons/admin/dashboard/arrow-20.svg" alt="" width="20" height="20" />
                </button>
              </div>
              <div class="month-panel__grid" id="month-panel-grid"></div>
              <button class="month-panel__today" id="month-panel-today" type="button">이번 달</button>
            </div>
          </div>
          <button class="month-nav__step" id="month-next" type="button" aria-label="다음 달">
            <img src="/aseets/icons/admin/dashboard/arrow-20.svg" alt="" width="20" height="20" />
          </button>
        </div>
      </header>

      <section class="chatbot-banner" aria-labelledby="chatbot-title">
        <div>
          <h2 id="chatbot-title">챗봇 현황</h2>
          <p><span class="js-month-name"><?= e($monthName) ?></span> 방문자 대비 챗봇 사용률 <strong id="chatbot-rate"><?= e((string) $stats['chatbotRate']) ?>%</strong></p>
        </div>
        <a class="chatbot-banner__link" href="/admin/chatbot.php">챗봇 관리 바로가기</a>
      </section>

      <div class="stat-row">
        <section class="dash-card visitor-card" aria-labelledby="visitor-title">
          <div class="visitor-card__head">
            <h2 id="visitor-title">방문자 현황</h2>
            <div class="visitor-card__total">
              <strong id="visitor-total"><?= e(number_format($stats['visitorTotal'])) ?></strong>
              <span><span class="js-month-name"><?= e($monthName) ?></span> 방문자 수</span>
            </div>
            <label class="week-select">
              <select id="week-select" aria-label="조회 주차"></select>
              <img src="/aseets/icons/admin/dashboard/arrow-down-14.svg" alt="" width="14" height="14" />
            </label>
          </div>
          <div class="bar-chart">
            <div class="bar-chart__axis" id="visitor-axis" aria-hidden="true">
              <span></span>
              <span></span>
              <span></span>
              <span>0</span>
            </div>
            <ol class="bar-chart__bars" id="visitor-bars"></ol>
          </div>
        </section>

        <section class="dash-card member-card" aria-labelledby="member-title">
          <div class="card-head">
            <h2 id="member-title">관리자 계정 현황</h2>
          </div>
          <div class="donut" id="member-donut" role="img" aria-label="최근 30일 로그인 계정 비율 <?= e((string) $members['activeRate']) ?>%" style="--withdrawn: <?= e((string) $members['activeRate']) ?>">
            <div class="donut__hole">
              <span>최근 30일 로그인</span>
              <strong id="member-withdrawn"><?= e((string) $members['activeRate']) ?>%</strong>
            </div>
          </div>
          <dl class="member-stats">
            <div><dt>전체 계정</dt><dd id="member-total"><?= e(number_format($members['total'])) ?> 명</dd></div>
            <div><dt><span class="js-month-name"><?= e($monthName) ?></span> 가입</dt><dd id="member-month"><?= e(number_format($members['month'])) ?> 명</dd></div>
            <div><dt>오늘 가입</dt><dd id="member-today"><?= e(number_format($members['today'])) ?> 명</dd></div>
          </dl>
        </section>
      </div>

      <section class="dash-card list-card" aria-labelledby="inquiry-title">
        <div class="card-head card-head--list">
          <h2 id="inquiry-title">최근 견적문의</h2>
          <a class="more-link" href="/admin/inquiries.php">
            전체보기
            <img src="/aseets/icons/admin/dashboard/arrow-right-20-primary.svg" alt="" width="20" height="20" />
          </a>
        </div>
        <div class="table-frame">
          <table class="dash-table">
            <colgroup>
              <col style="width: 80px" />
              <col style="width: 80px" />
              <col style="width: 128px" />
              <col />
              <col style="width: 156px" />
              <col style="width: 128px" />
            </colgroup>
            <thead>
              <tr>
                <th scope="col">no.</th>
                <th scope="col">작성자</th>
                <th scope="col">회사명</th>
                <th scope="col" class="is-left">문의내용</th>
                <th scope="col">접수일</th>
                <th scope="col">상태</th>
              </tr>
            </thead>
            <tbody id="inquiry-rows"></tbody>
          </table>
        </div>
        <nav class="pagination" id="inquiry-pagination" aria-label="견적문의 페이지 이동"></nav>
      </section>

      <section class="dash-card list-card" aria-labelledby="post-title">
        <div class="card-head card-head--list">
          <h2 id="post-title">최근 게시글</h2>
          <a class="more-link" href="/admin/board.php">
            전체보기
            <img src="/aseets/icons/admin/dashboard/arrow-right-20-primary.svg" alt="" width="20" height="20" />
          </a>
        </div>
        <div class="post-tabs" id="post-tabs" role="tablist" aria-label="게시글 분야"></div>
        <div class="table-frame">
          <table class="dash-table">
            <colgroup>
              <col style="width: 80px" />
              <col style="width: 128px" />
              <col />
              <col style="width: 80px" />
              <col style="width: 80px" />
              <col style="width: 80px" />
              <col style="width: 80px" />
            </colgroup>
            <thead>
              <tr>
                <th scope="col">no.</th>
                <th scope="col">분야</th>
                <th scope="col" class="is-left">제목</th>
                <th scope="col">작성자</th>
                <th scope="col">등록일</th>
                <th scope="col">조회</th>
                <th scope="col">노출</th>
              </tr>
            </thead>
            <tbody id="post-rows"></tbody>
          </table>
        </div>
        <nav class="pagination" id="post-pagination" aria-label="게시글 페이지 이동"></nav>
      </section>
    </main>
<?php
view('admin/layout-end');
view('admin/foot');
