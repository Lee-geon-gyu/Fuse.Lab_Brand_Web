<?php
require __DIR__ . '/app/bootstrap.php';

$category = (string) ($_GET['category'] ?? '');
if (!isset(PORTFOLIO_CATEGORIES[$category])) {
    $category = '';
}
$page = min(50, max(1, (int) ($_GET['page'] ?? 1)));

// 더보기(js/portfolio-list.js): ?partial=1&page=N → N 페이지 항목 HTML 만 JSON 으로
if (isset($_GET['partial'])) {
    $list = portfolio_public_list($category, ($page - 1) * PORTFOLIO_PAGE_SIZE, PORTFOLIO_PAGE_SIZE);
    ob_start();
    view('site/portfolio-items', ['items' => $list['items'], 'rowStart' => intdiv(($page - 1) * PORTFOLIO_PAGE_SIZE, 3)]);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        ['ok' => true, 'html' => ob_get_clean(), 'count' => count($list['items']), 'hasMore' => $list['hasMore']],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

track_visit();

// 첫 화면은 1 ~ $page 페이지를 한 번에 (새로고침해도 불러온 만큼 유지)
$list = portfolio_public_list($category, 0, $page * PORTFOLIO_PAGE_SIZE);
$filters = ['' => 'ALL'] + PORTFOLIO_CATEGORIES;

view('site/head', ['pageTitle' => '포트폴리오 목록', 'styles' => ['/css/portfolio-list.css']]);
?>
<?php view('site/header', ['isHome' => false, 'headerTheme' => 'dark']); ?>

    <main>
      <section class="portfolio-sub-sec">
        <div class="portfolio-sub-grid" data-portfolio-list data-endpoint="/portfolio.php" data-category="<?= e($category) ?>" data-page="<?= $page ?>">
          <div class="head-box">
            <div class="left-box">
              <h2>WEB EXPERIENCES CRAFTED</h2>
              <h3>portfolio</h3>
            </div>
            <div class="right-box">
              <form class="portfolio-filter-form" method="get" action="/portfolio.php">
                <ul class="filter-list">
<?php foreach ($filters as $key => $label): ?>
                  <li>
                    <button type="submit" name="category" value="<?= e($key) ?>" data-category="<?= e($key) ?>" class="marquee-btn<?= $key === $category ? ' active' : '' ?>" aria-pressed="<?= $key === $category ? 'true' : 'false' ?>"><span class="marquee-text"><?= e($label) ?></span></button>
                  </li>
<?php endforeach; ?>
                </ul>
              </form>
            </div>
          </div>
          <div class="body-box">
            <div class="portfolio-rows" data-portfolio-rows aria-live="polite">
<?php view('site/portfolio-items', ['items' => $list['items'], 'rowStart' => 0]); ?>
            </div>
            <p class="portfolio-empty" data-portfolio-empty<?= $list['items'] ? ' hidden' : '' ?>>등록된 포트폴리오가 없습니다.</p>
            <div class="btn-box">
              <button class="plus-btn" type="button" aria-label="포트폴리오 더보기" data-portfolio-more<?= $list['hasMore'] ? '' : ' hidden' ?>>
                <img src="/aseets/icons/portfoilo-sub/add-ico.svg" alt="플러스아이콘" />
              </button>
            </div>
          </div>
        </div>
      </section>
    </main>
    <script src="/js/portfolio-list.js" defer></script>

<?php view('site/footer', ['isHome' => false]); ?>
