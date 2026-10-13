<?php
/**
 * 사용자 페이지 <head> ~ <body> 시작
 * @var string      $pageTitle
 * @var string|null $bodyClass
 * @var bool|null   $isHome    true 면 메인 전용 Lenis 스크롤 로드
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
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;800&family=Playfair+Display:ital,wght@1,400;1,500&display=swap" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable-dynamic-subset.min.css" />
<?php if (!empty($isHome)): ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lenis@1.3.26/dist/lenis.css" />
<?php endif; ?>
    <link rel="stylesheet" href="/css/common.css" />
    <link rel="stylesheet" href="/css/loader.css" />
    <link rel="stylesheet" href="/css/hero.css" />
    <link rel="stylesheet" href="/css/divider.css" />
    <link rel="stylesheet" href="/css/font.css" />
    <link rel="stylesheet" href="/css/GYU.css" />
    <link rel="stylesheet" href="/css/WON.css" />
    <link rel="stylesheet" href="/css/SURO.css" />
    <link rel="stylesheet" href="/css/chatbot-widget.css" />
    <link rel="stylesheet" href="/css/site-overflow.css" />
<?php foreach ($styles as $href): ?>
    <link rel="stylesheet" href="<?= e($href) ?>" />
<?php endforeach; ?>
<?php if ($subpage !== null && trim($subpage['css']) !== ''): ?>
    <style data-subpage-css>
<?= subpage_inline($subpage['css'], 'style') ?>
    </style>
<?php endif; ?>
  </head>
  <body<?= isset($bodyClass) ? ' class="' . e($bodyClass) . '"' : '' ?>>
