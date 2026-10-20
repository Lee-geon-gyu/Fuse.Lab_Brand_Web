<?php
/**
 * 사용자 페이지 <head> ~ <body> 시작
 * @var string      $pageTitle
 * @var string|null $bodyClass
 * @var bool|null   $isHome    true 면 메인 전용 로더·폰트 대기·스크롤 위치 초기화
 * @var string[]    $styles    페이지 전용 CSS 경로 (공통 CSS 뒤에 로드)
 */
$styles ??= [];
// 서브페이지 관리에서 이 페이지에 넣은 CSS (관리자 입력 그대로)
$subpage = subpage_current();
?>
<!doctype html>
<html lang="ko">
  <head>
    <meta charset="UTF-8" />
<?php if (!empty($isHome)): ?>
    <script>
      // Set this before the browser restores the previous scroll position.
      if ("scrollRestoration" in history) history.scrollRestoration = "manual";
    </script>
<?php endif; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="아이디어에서 운영까지, 기획·디자인·개발을 하나의 팀에서 연결하는 fuse.lab" />
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="fuse.lab" />
    <meta property="og:title" content="<?= e($pageTitle) ?>" />
    <meta property="og:description" content="아이디어에서 운영까지, 기획·디자인·개발을 하나의 팀에서 연결하는 fuse.lab" />
    <meta property="og:image" content="<?= e(site_url()) ?>/aseets/img/og-image.jpg" />
    <meta property="og:image:width" content="1200" />
    <meta property="og:image:height" content="630" />
    <meta name="twitter:card" content="summary_large_image" />
    <link rel="icon" href="/favicon.ico" sizes="any" />
    <link rel="icon" type="image/png" sizes="32x32" href="/aseets/img/favicon-32.png" />
    <link rel="apple-touch-icon" href="/aseets/img/apple-touch-icon.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;800&family=Playfair+Display:ital,wght@1,400;1,500&display=swap" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable-dynamic-subset.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lenis@1.3.26/dist/lenis.css" />
<?php foreach ([
    '/css/common.css', '/css/loader.css', '/css/hero.css', '/css/divider.css', '/css/font.css',
    '/css/GYU.css', '/css/WON.css', '/css/SURO.css', '/css/chatbot-widget.css', '/css/motion.css',
    '/css/site-overflow.css', '/css/cursor.css', ...$styles,
] as $href): ?>
    <link rel="stylesheet" href="<?= e(asset($href)) ?>" />
<?php endforeach; ?>
<?php if ($subpage !== null && trim($subpage['css']) !== ''): ?>
    <style data-subpage-css>
<?= subpage_inline($subpage['css'], 'style') ?>
    </style>
<?php endif; ?>
<?php if (!empty($isHome)): ?>
    <script>
      // 위 스타일시트(@font-face)가 적용된 뒤 실행됨. 로더·히어로 첫 화면 폰트를 바로 받기 시작하고,
      // 받을 때까지 로더 문구 등장을 멈춰 대체 폰트 → 웹폰트 교체가 보이지 않게 함 (css/loader.css)
      (() => {
        const root = document.documentElement;
        root.classList.add("fonts-pending");
        const release = () => root.classList.remove("fonts-pending");
        Promise.all([
          '300 1em "Inter"',
          '800 1em "Inter"',
          'italic 500 1em "Playfair Display"',
          '500 1em "Pretendard Variable"',
        ].map((font) => document.fonts.load(font, "Aa가"))).then(release, release);
        setTimeout(release, 2500);
      })();
    </script>
<?php endif; ?>
  </head>
  <body<?= isset($bodyClass) ? ' class="' . e($bodyClass) . '"' : '' ?>>
