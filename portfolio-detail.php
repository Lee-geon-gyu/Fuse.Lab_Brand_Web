<?php
require __DIR__ . '/app/bootstrap.php';

track_visit();

$portfolio = portfolio_find((int) ($_GET['id'] ?? 0), true);
// slot() 문구는 관리자 > 서브페이지 관리 > portfolio-detail.php 에서 수정

if ($portfolio === null) {
    http_response_code(404);
    view('site/head', ['pageTitle' => '포트폴리오를 찾을 수 없습니다']);
    view('site/header', ['isHome' => false, 'headerTheme' => 'dark']);
?>

    <main>
      <section class="portfolio-detail-sec-Overview">
        <div class="portfolio-detail-Overview-grid">
          <h2 class="title">Not Found</h2>
          <div class="body-box">
            <h2 class="body-title">포트폴리오를 찾을 수 없습니다</h2>
            <p>주소가 잘못되었거나 삭제·비공개된 포트폴리오입니다.</p>
          </div>
          <div class="btn-box">
            <a href="/portfolio.php" class="marquee-btn"><span class="marquee-text"><?= slot('list_button', '목록으로') ?></span></a>
          </div>
        </div>
      </section>
    </main>

<?php
    view('site/footer', ['isHome' => false]);
    exit;
}

$title = $portfolio['title'];
$meta = array_filter([
    'CLIENT' => $portfolio['client'],
    'TYPE' => $portfolio['type'],
    'DATE' => $portfolio['project_date'],
], static fn (string $value): bool => $value !== '');

view('site/head', ['pageTitle' => slot_value('page_title', '포트폴리오 상세') . ' ' . $title]);
?>
<?php view('site/header', ['isHome' => false, 'headerTheme' => 'dark']); ?>

    <main>
      <section class="portfolio-detail-sec-full-img">
        <img src="<?= e($portfolio['hero_image'] ?: $portfolio['thumbnail']) ?>" alt="<?= e($title) ?> 이미지" />
        <img class="arrow-down" src="/aseets/imgs/portfolio-sub/arrow-down.svg" alt="arrow-down" />
      </section>
      <section class="portfolio-detail-sec-Overview">
        <div class="portfolio-detail-Overview-grid">
          <h2 class="title"><?= slot('overview_title', 'Overview') ?></h2>
          <div class="body-box">
            <h2 class="body-title"><?= e($title) ?></h2>
            <p>
              <?= nl2br(e($portfolio['summary'])) ?>

            </p>
          </div>
<?php if ($meta): ?>
          <ul class="meta-box">
<?php foreach ($meta as $label => $text): ?>
            <li class="meta-item">
              <div class="meta-line"></div>
              <h3 class="meta-title"><?= $label ?></h3>
              <p class="meta-text"><?= e($text) ?></p>
            </li>
<?php endforeach; ?>
          </ul>
<?php endif; ?>
<?php if ($portfolio['site_url'] !== ''): ?>
          <div class="btn-box">
            <a href="<?= e($portfolio['site_url']) ?>" target="_blank" rel="noopener noreferrer" title="새창열림" class="marquee-btn"><span class="marquee-text"><?= slot('site_button', '사이트 바로가기') ?></span></a>
          </div>
<?php endif; ?>
        </div>
      </section>
      <section class="portfolio-detail-sec-pages">
<?php if (!empty($portfolio['detail_image'])): ?>
        <div class="portfolio-detail-sec-pages-grid">
          <img src="<?= e($portfolio['detail_image']) ?>" alt="<?= e($title) ?> 웹 이미지" />
        </div>
<?php endif; ?>
        <div class="btn-box">
          <a href="/portfolio.php" class="marquee-btn"><span class="marquee-text"><?= slot('list_button', '목록으로') ?></span></a>
        </div>
      </section>
    </main>

<?php view('site/footer', ['isHome' => false]); ?>
