<?php
/**
 * 사용자 페이지 <head> ~ <body> 시작
 * @var string      $pageTitle
 * @var string|null $bodyClass
 */
?>
<!doctype html>
<html lang="ko">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;800&family=Playfair+Display:ital,wght@1,400;1,500&display=swap" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable-dynamic-subset.min.css" />
    <link rel="stylesheet" href="/css/common.css" />
    <link rel="stylesheet" href="/css/loader.css" />
    <link rel="stylesheet" href="/css/hero.css" />
    <link rel="stylesheet" href="/css/divider.css" />
    <link rel="stylesheet" href="/css/font.css" />
    <link rel="stylesheet" href="/css/GYU.css" />
    <link rel="stylesheet" href="/css/WON.css" />
    <link rel="stylesheet" href="/css/SURO.css" />
  </head>
  <body<?= isset($bodyClass) ? ' class="' . e($bodyClass) . '"' : '' ?>>
