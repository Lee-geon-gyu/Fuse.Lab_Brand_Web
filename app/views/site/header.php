<?php
/**
 * 사용자 GNB
 * @var bool   $isHome      메인이면 해시만, 아니면 /#섹션
 * @var string $headerTheme 'light' | 'dark'
 */
$home = $isHome ? '' : '/';
?>
    <header class="header" id="header" data-theme="<?= e($headerTheme) ?>">
      <div class="header__inner">
        <a class="header__logo" href="<?= $home ?>#hero" aria-label="fuse.lab 홈">
          <img class="header__logo-img header__logo-img--light" src="/aseets/img/logo-light.svg" width="177" height="36" alt="fuse.lab" />
          <img class="header__logo-img header__logo-img--dark" src="/aseets/img/logo-dark.svg" width="177" height="36" alt="" />
        </a>
        <nav class="gnb" aria-label="주 메뉴">
          <ul class="gnb__list">
            <li><a href="<?= $home ?>#hero">Home</a></li>
            <li><a href="<?= $home ?>#gyu-main">Portfolio</a></li>
            <li><a href="<?= $home ?>#gyu-mvp">Services</a></li>
            <li><a href="<?= $home ?>#why-us">Why Us</a></li>
            <li><a href="<?= $home ?>#contact">Contact</a></li>
          </ul>
        </nav>
      </div>
    </header>
