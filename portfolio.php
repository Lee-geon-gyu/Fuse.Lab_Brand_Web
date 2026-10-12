<?php
require __DIR__ . '/app/bootstrap.php';

view('site/head', ['pageTitle' => '포트폴리오 목록']);
?>
<?php view('site/header', ['isHome' => false, 'headerTheme' => 'dark']); ?>

    <main>
      <section class="portfolio-sub-sec">
        <div class="portfolio-sub-grid">
          <div class="head-box">
            <div class="left-box">
              <h2>WEB EXPERIENCES CRAFTED</h2>
              <h3>portfolio</h3>
            </div>
            <div class="right-box">
              <ul class="filter-list">
                <li>
                  <button type="button" class="marquee-btn active" aria-pressed="true"><span class="marquee-text">ALL</span></button>
                </li>
                <li>
                  <button type="button" class="marquee-btn" aria-pressed="false"><span class="marquee-text">MVP</span></button>
                </li>
                <li>
                  <button type="button" class="marquee-btn" aria-pressed="false"><span class="marquee-text">Branding</span></button>
                </li>
                <li>
                  <button type="button" class="marquee-btn" aria-pressed="false"><span class="marquee-text">UI/UX</span></button>
                </li>
                <li>
                  <button type="button" class="marquee-btn" aria-pressed="false"><span class="marquee-text">Front</span></button>
                </li>
                <li>
                  <button type="button" class="marquee-btn" aria-pressed="false"><span class="marquee-text">Detail</span></button>
                </li>
              </ul>
            </div>
          </div>
          <div class="body-box">
            <div class="top-box">
              <ul class="body-list">
                <li class="body-list-item">
                  <a href="#"><img src="/aseets/imgs/portfolio-sub/jw.png" alt="중외제약" /> <span>JW 중외제약</span></a>
                </li>
                <li class="body-list-item margin-item">
                  <a href="#"><img src="/aseets/imgs/portfolio-sub/cj.png" alt="올리브영" /> <span>CJ 올리브영</span></a>
                </li>
                <li class="body-list-item">
                  <a href="/portfolio-detail.php"><img src="/aseets/imgs/portfolio-sub/kumu.png" alt="쿠무" /> <span>쿠무</span></a>
                </li>
              </ul>
            </div>
            <div class="bottom-box">
              <ul class="body-list">
                <li class="body-list-item">
                  <a href="#"><img src="/aseets/imgs/portfolio-sub/star.png" alt="스타스케이프" /> <span>스타스케이프</span></a>
                </li>
                <li class="body-list-item margin-item">
                  <a href="#"><img src="/aseets/imgs/portfolio-sub/Geonil.png" alt="건일제약" /> <span>건일제약</span></a>
                </li>
                <li class="body-list-item">
                  <a href="#"><img src="/aseets/imgs/portfolio-sub/eatgo.png" alt="잇고" /> <span>잇고</span></a>
                </li>
              </ul>
            </div>
            <div class="btn-box">
              <button class="plus-btn" type="button" aria-label="추가">
                <img src="/aseets/icons/portfoilo-sub/add-ico.svg" alt="플러스아이콘" />
              </button>
            </div>
          </div>
        </div>
      </section>
    </main>

<?php view('site/footer', ['isHome' => false]); ?>
