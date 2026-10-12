<?php
/**
 * 관리자 페이지 <head> ~ <body> 시작
 * @var string                $pageTitle
 * @var string[]              $styles    페이지 CSS 경로
 * @var string[]              $scripts   페이지 JS 경로 (defer)
 * @var array<string, string> $bodyData  <body data-*> 값 (JS 에서 dataset 으로 사용)
 */
$styles ??= [];
$scripts ??= [];
$bodyData ??= [];
?>
<!doctype html>
<html lang="ko">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= e($pageTitle) ?> | fuse.lab</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable-dynamic-subset.min.css" />
<?php foreach ($styles as $href): ?>
    <link rel="stylesheet" href="<?= e($href) ?>" />
<?php endforeach; ?>
<?php foreach ($scripts as $src): ?>
    <script src="<?= e($src) ?>" defer></script>
<?php endforeach; ?>
  </head>
  <body<?php foreach ($bodyData as $key => $value): ?> data-<?= e($key) ?>="<?= e($value) ?>"<?php endforeach; ?>>
