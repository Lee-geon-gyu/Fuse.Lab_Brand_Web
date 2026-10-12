<?php
require __DIR__ . '/app/bootstrap.php';

view('site/head', ['pageTitle' => '포트폴리오 상세 쿠무']);
?>
<?php view('site/header', ['isHome' => false, 'headerTheme' => 'dark']); ?>

    <main>
      <section class="portfolio-detail-sec-full-img">
        <img src="/aseets/imgs/portfolio-sub/back.png" alt="쿠무 이미지" />
        <img class="arrow-down" src="/aseets/imgs/portfolio-sub/arrow-down.svg" alt="arrow-down" />
      </section>
      <section class="portfolio-detail-sec-Overview">
        <div class="portfolio-detail-Overview-grid">
          <h2 class="title">Overview</h2>
          <div class="body-box">
            <h2 class="body-title"></h2>
            <p>
              쿠무는 사람과 반려견이 함께 식사와 디저트를 즐기며 특별한 시간을 만들어가는 반려동물 동반 카페·베이커리 브랜드입니다.<br />
              분산된 메뉴와 이용 정보, 브랜드 콘텐츠를 하나의 웹사이트 안에서 연결하고, 계절에 따라 변화하는 메뉴와 클래스, 이벤트를 자연스럽게 탐색할 수 있도록 반응형 웹사이트를 설계했습니다.
            </p>
          </div>
          <ul class="meta-box">
            <li class="meta-item">
              <div class="meta-line"></div>
              <h3 class="meta-title">CLIENT</h3>
              <p class="meta-text">Kumu</p>
            </li>
            <li class="meta-item">
              <div class="meta-line"></div>
              <h3 class="meta-title">TYPE</h3>
              <p class="meta-text">Branding, UI/UX Design, Responsive Web</p>
            </li>
            <li class="meta-item">
              <div class="meta-line"></div>
              <h3 class="meta-title">DATE</h3>
              <p class="meta-text">2025.10 ~ 2025.12</p>
            </li>
          </ul>
          <div class="btn-box">
            <a href="https://tnfh23-web.github.io/kumu/" target="_blank" rel="noopener noreferrer" title="새창열림" class="marquee-btn"><span class="marquee-text">사이트 바로가기</span></a>
          </div>
        </div>
      </section>
      <section class="portfolio-detail-sec-pages">
        <div class="portfolio-detail-sec-pages-grid">
          <img src="/aseets/imgs/portfolio-sub/kumu-pro.png" alt="쿠무 웹 이미지" />
        </div>
        <div class="btn-box">
          <a href="/portfolio.php" target="_blank" rel="noopener noreferrer" title="새창열림" class="marquee-btn"><span class="marquee-text">목록으로</span></a>
        </div>
      </section>
    </main>

<?php view('site/footer', ['isHome' => false]); ?>
