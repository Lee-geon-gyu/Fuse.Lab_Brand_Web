<?php
require __DIR__ . '/app/bootstrap.php';
track_visit();

// 포트폴리오 슬라이드 = 관리자 게시판 '포트폴리오' 노출 글 (첫 글은 first 디자인, 나머지는 second 디자인)
$slides = portfolio_slides();
$slideTotal = sprintf('%02d', count($slides));
// 관리자로 로그인한 상태면 슬라이드마다 수정 링크, 화면 아래에 추가 버튼
$isAdmin = current_admin() !== null;

view('site/head', ['pageTitle' => 'fuse.lab', 'bodyClass' => 'is-loading', 'isHome' => true]);
?>
    <!-- LOADER : Figma 1:98 -->
    <div class="loader" id="loader" aria-hidden="true">
      <img
        class="loader__ring"
        src="/aseets/img/loader-ring.png"
        width="869"
        height="837"
        alt=""
      />
      <p class="loader__text">
        <span class="loader__line">Building</span>
        <span class="loader__line loader__line--accent">experiences</span>
        <span class="loader__line">inspire.</span>
      </p>
    </div>

<?php view('site/header', ['isHome' => true, 'headerTheme' => 'light']); ?>

    <main>
      <!-- HERO : Figma 1:3 -->
      <section class="hero" id="hero" data-header-theme="light">
        <div class="hero__visual" aria-hidden="true"></div>
        <h1 class="hero__title">Our Studio</h1>
        <div class="hero__bottom">
          <div class="hero__info">
            <p class="hero__label">OUR APPROACH</p>
            <p class="hero__tags">
              Strategy • Identity • Digital<br />Campaign • Experience • Content
            </p>
          </div>
          <div class="hero__logo">
            <img src="/aseets/img/logo-hero-large.svg" alt="fuse.lab" />
          </div>
        </div>
      </section>

      <a class="skip-nav" href="#gyu-main">?? ????</a>
      <!-- ==================== Gyu Lane Start ==================== -->
      <a class="skip-nav" href="#gyu-main">본문 바로가기</a>

      <div id="wrap" class="gyu-wrap">
        <section id="gyu-main" class="gyu-story" data-header-theme="light">
          <div class="gyu-stage">
            <div class="gyu-track">
<?php foreach ($slides as $index => $slide):
    $number = sprintf('%02d', $index + 1);
    $isFirst = $index === 0;
    $headingTag = $isFirst ? 'h1' : 'h2';
?>
              <article
                id="gyu-project-<?= $number ?>"
                class="gyu-project gyu-project--<?= $isFirst ? 'first' : 'second' ?>"
                aria-labelledby="gyu-title-<?= $number ?>"
              >
                <img
                  class="gyu-project__image"
                  src="<?= e($slide['image']) ?>"
                  alt=""
<?php if ($index < 2): ?>
                  loading="eager"
                  decoding="sync"
                  fetchpriority="high"
<?php endif; ?>
                />
                <div
                  class="gyu-project__shade<?= $isFirst ? '' : ' gyu-project__shade--second' ?>"
                  aria-hidden="true"
                ></div>

                <div class="gyu-project__content">
                  <div class="gyu-project__copy">
                    <p class="gyu-project__index" aria-label="<?= $index + 1 ?> / <?= count($slides) ?>">
                      <strong><?= $number ?></strong><span>/</span><span><?= $slideTotal ?></span>
                    </p>

                    <div class="gyu-project__heading">
<?php if ($slide['eyebrow'] !== ''): ?>
                      <p class="gyu-project__eyebrow" title="<?= e($slide['eyebrow']) ?>"><?= e($slide['eyebrow']) ?></p>
<?php endif; ?>
                      <<?= $headingTag ?> id="gyu-title-<?= $number ?>" class="gyu-project__title" title="<?= e($slide['title']) ?>">
                        <?= e($slide['title']) ?>
                      </<?= $headingTag ?>>
                    </div>

                    <p class="gyu-project__description">
                      <?= nl2br(e($slide['content']), false) ?>
                    </p>
                  </div>

<?php if ($slide['link_id'] !== null): ?>
                  <a class="gyu-project__button marquee-btn" href="/portfolio-detail.php?id=<?= (int) $slide['link_id'] ?>">
                    <span class="marquee-text">VIEW PROJECT</span>
                  </a>
<?php else: ?>
                  <button class="gyu-project__button marquee-btn" type="button">
                    <span class="marquee-text">VIEW PROJECT</span>
                  </button>
<?php endif; ?>
                </div>
<?php if ($isAdmin): ?>
                <a class="admin-edit-link" href="/admin/post-edit.php?<?= e(http_build_query(['id' => $slide['id'], 'return' => '/'])) ?>">이 슬라이드 수정</a>
<?php endif; ?>
              </article>
<?php endforeach; ?>
            </div>
          </div>
        </section>
<?php if ($isAdmin): ?>
        <nav class="admin-bar" aria-label="관리자 메뉴">
          <span>관리자 모드</span>
          <a href="/admin/post-edit.php?<?= e(http_build_query(['category' => 'portfolio', 'return' => '/'])) ?>">포트폴리오 슬라이드 추가</a>
          <a href="/admin/board.php">게시판</a>
        </nav>
<?php endif; ?>

        <!-- DIVIDER : Figma 1:47 -->
        <section class="divider" id="divider" data-header-theme="dark">
          <div class="divider__visual">
            <div class="divider__sphere">
              <img
                src="/aseets/img/divider-sphere.png"
                alt=""
                width="1536"
                height="1024"
              />
            </div>
            <div class="divider__text">
              <h2 class="divider__title">From Idea to System.</h2>
              <p class="divider__desc">
                아이디어에서 끝나지 않고, 실제로 작동하는 구조까지 만듭니다.
              </p>
            </div>
          </div>
          <div
            class="divider__marquee"
            role="img"
            aria-label="WEB, BRAND, DESIGN, UI/UX"
          >
            <div class="divider__track" aria-hidden="true">
              <div class="divider__group">
                <span>WEB</span><span>•</span><span>BRAND</span><span>•</span
                ><span>DESIGN</span><span>•</span><span>UI/UX</span><span>•</span
                ><span>WEB</span><span>•</span><span>BRAND</span><span>•</span
                ><span>DESIGN</span><span>•</span><span>UI/UX</span><span>•</span>
              </div>
              <div class="divider__group">
                <span>WEB</span><span>•</span><span>BRAND</span><span>•</span
                ><span>DESIGN</span><span>•</span><span>UI/UX</span><span>•</span
                ><span>WEB</span><span>•</span><span>BRAND</span><span>•</span
                ><span>DESIGN</span><span>•</span><span>UI/UX</span><span>•</span>
              </div>
            </div>
          </div>
        </section>

        <!-- ==================== Gyu Lane - MVP Start ==================== -->
        <section id="gyu-mvp" class="gyu-mvp" aria-label="Services" data-header-theme="light">
          <div class="gyu-mvp__stage">
            <div
              class="gyu-mvp__deck"
              tabindex="0"
              role="group"
              aria-roledescription="카드 슬라이더"
              aria-label="서비스 카드. 좌우 화살표 키로 이동할 수 있습니다."
            >
              <article class="gyu-service-card">
                <div class="gyu-service-card__visual">
                  <img
                    class="gyu-service-card__image"
                    src="/aseets/images/section-mvp/image-1.png"
                    alt="노트북이 놓인 밝은 공간"
                    loading="eager"
                    fetchpriority="high"
                  />
                  <div class="gyu-service-card__shade" aria-hidden="true"></div>
                  <div class="gyu-service-card__hero">
                    <strong>01</strong>
                    <h2>Web Service · MVP</h2>
                    <div>
                      <h3>아이디어를 실제 서비스로 구현합니다.</h3>
                      <p>
                        초기 아이디어를 바탕으로 핵심 기능과 사용자 흐름을
                        정리하고,<br />필요한 화면과 기능을 구체화합니다.
                        기획부터 UI 설계, 개발까지<br />하나의 흐름으로 연결해
                        실제로 작동하고 검증할 수 있는 웹서비스와 MVP를
                        만듭니다.
                      </p>
                    </div>
                  </div>
                </div>
              </article>

              <article class="gyu-service-card">
                <div class="gyu-service-card__visual">
                  <img
                    class="gyu-service-card__image"
                    src="/aseets/images/section-mvp/image-2.png"
                    alt="브랜드 문구가 인쇄된 카드"
                    loading="eager"
                  />
                  <div class="gyu-service-card__shade" aria-hidden="true"></div>
                  <div class="gyu-service-card__hero">
                    <strong>02</strong>
                    <h2>Brand Website</h2>
                    <div>
                      <h3>브랜드의 정체성을 웹 경험으로 연결합니다.</h3>
                      <p>
                        브랜드가 전달하고자 하는 메시지와 이미지를 먼저
                        이해하고,<br />콘텐츠 구조와 화면 흐름을 설계합니다.
                        비주얼과 인터랙션까지<br />일관된 방향으로 구현해
                        브랜드의 성격이 자연스럽게 드러나는 웹사이트를
                        완성합니다.
                      </p>
                    </div>
                  </div>
                </div>
              </article>

              <article class="gyu-service-card">
                <div class="gyu-service-card__visual">
                  <img
                    class="gyu-service-card__image"
                    src="/aseets/images/section-mvp/image-3.png"
                    alt="공중에 떠 있는 투명 디스플레이"
                    loading="eager"
                  />
                  <div class="gyu-service-card__shade" aria-hidden="true"></div>
                  <div class="gyu-service-card__hero">
                    <strong>03</strong>
                    <h2>Automation · Internal Tool</h2>
                    <div>
                      <h3>반복되는 업무를 효율적인 시스템으로 바꿉니다.</h3>
                      <p>
                        실제 업무 과정에서 반복되거나 관리가 어려운 작업을
                        파악하고,<br />필요한 기능과 데이터를 정리합니다. 복잡한
                        업무 흐름을 웹 기반<br />내부툴과 자동화 시스템으로
                        전환해 더 빠르고 편하게 관리할 수 있도록 만듭니다.
                      </p>
                    </div>
                  </div>
                </div>
              </article>

              <article class="gyu-service-card">
                <div class="gyu-service-card__visual">
                  <img
                    class="gyu-service-card__image"
                    src="/aseets/images/section-mvp/image-4.png"
                    alt="사용자 경험을 설계한 와이어프레임"
                    loading="eager"
                  />
                  <div class="gyu-service-card__shade" aria-hidden="true"></div>
                  <div class="gyu-service-card__hero">
                    <strong>04</strong>
                    <h2>UX · UI Design</h2>
                    <div>
                      <h3>사용자 중심의 경험을 설계합니다.</h3>
                      <p>
                        사용자의 목표와 행동을 분석해 필요한 정보와 기능을
                        정리하고,<br />직관적인 화면 구조와 인터랙션을
                        설계합니다. 브랜드의 감도와<br />사용성을 함께 고려해
                        이해하기 쉽고 편안한 디지털 경험을 만듭니다.
                      </p>
                    </div>
                  </div>
                </div>
              </article>

              <article class="gyu-service-card">
                <div class="gyu-service-card__visual">
                  <img
                    class="gyu-service-card__image"
                    src="/aseets/images/section-mvp/image-5.png"
                    alt="Publish Connect Share 문구가 적힌 책"
                    loading="eager"
                  />
                  <div class="gyu-service-card__shade" aria-hidden="true"></div>
                  <div class="gyu-service-card__hero">
                    <strong>05</strong>
                    <h2>Content · Community</h2>
                    <div>
                      <h3>콘텐츠가 연결되고 확산되는 플랫폼을 만듭니다.</h3>
                      <p>
                        콘텐츠를 만들고 발견하고 공유하는 흐름을 세심하게
                        설계하고,<br />운영에 필요한 관리 기능까지 함께
                        구성합니다. 사용자와 콘텐츠가<br />자연스럽게 연결되며
                        지속적으로 성장할 수 있는 서비스를 구현합니다.
                      </p>
                    </div>
                  </div>
                </div>
              </article>

              <article class="gyu-service-card">
                <div class="gyu-service-card__visual">
                  <img
                    class="gyu-service-card__image"
                    src="/aseets/images/section-mvp/image-6.png"
                    alt="빛을 굴절시키는 투명한 원형 오브젝트"
                    loading="eager"
                  />
                  <div class="gyu-service-card__shade" aria-hidden="true"></div>
                  <div class="gyu-service-card__hero">
                    <strong>06</strong>
                    <h2>Digital Experience</h2>
                    <div>
                      <h3>브랜드와 사용자가 만나는 새로운 경험을 만듭니다.</h3>
                      <p>
                        아이디어와 기술, 비주얼을 하나의 방향으로 연결하고<br />프로젝트에
                        맞는 표현 방식과 상호작용을 설계합니다.<br />화면을 넘어
                        오래 기억되는 명확하고 인상적인 디지털 경험을
                        완성합니다.
                      </p>
                    </div>
                  </div>
                </div>
              </article>
            </div>
          </div>
        </section>
        <!-- ===================== Gyu Lane - MVP End ===================== -->
      </div>
      <!-- ===================== Gyu Lane End ===================== -->

      <section class="why-sec" id="why-us" data-header-theme="dark">
        <div class="why-inner">
          <div class="title-box">
            <div class="title-top-box">
              <h2 class="title-text title-ls">why</h2>
              <img src="/aseets/imgs/logo.png" alt="로고" />
            </div>
            <div class="title-bottom-box">
              <p>아이디어에서 운영에 이르기까지 디지털 경험을 구축합니다.</p>
            </div>
          </div>
          <div class="why-body-box">
            <div class="why-item is-active">
              <div class="why-item-inner">
                <div class="why-item-head">
                  <span class="why-item-num title-ls">CONNECT</span>
                  <h3 class="title-ls">One Team, Full Build</h3>
                </div>

                <div class="why-item-cen">
                  <img src="/aseets/imgs/why-sec/why-sec-1.png" alt="why-img" />
                </div>

                <div class="why-item-bottom">
                  <p>기획부터 디자인, 개발까지 하나의 팀에서 연결하고 <br />프로젝트 전 과정을 끊김 없이 이어갑니다.</p>
                </div>
              </div>
            </div>
            <div class="why-item">
              <div class="why-item-inner">
                <div class="why-item-head">
                  <span class="why-item-num title-ls">CUSTOM</span>
                  <h3 class="title-ls">Beyond Templates</h3>
                </div>

                <div class="why-item-cen">
                  <img src="/aseets/imgs/why-sec/why-sec-2.png" alt="why-img" />
                </div>

                <div class="why-item-bottom">
                  <p>정해진 템플릿이나 웹빌더의 한계에 맞추지 않고 <br />프로젝트에 필요한 화면과 기능을 직접 구현합니다.</p>
                </div>
              </div>
            </div>
            <div class="why-item">
              <div class="why-item-inner">
                <div class="why-item-head">
                  <span class="why-item-num title-ls">OPERATE</span>
                  <h3 class="title-ls">Front to Admin</h3>
                </div>

                <div class="why-item-cen">
                  <img src="/aseets/imgs/why-sec/why-sec-3.png" alt="why-img" />
                </div>

                <div class="why-item-bottom">
                  <p>사용자가 보는 프론트 화면뿐만 아니라 <br />실제 운영에 필요한 관리자 시스템까지 함께 만듭니다.</p>
                </div>
              </div>
            </div>
            <div class="why-item">
              <div class="why-item-inner">
                <div class="why-item-head">
                  <span class="why-item-num title-ls">SYSTEMIZE</span>
                  <h3 class="title-ls">Built as a System</h3>
                </div>

                <div class="why-item-cen">
                  <img src="/aseets/imgs/why-sec/why-sec-4.png" alt="why-img" />
                </div>

                <div class="why-item-bottom">
                  <p>페이지 하나를 만드는 데서 끝나지 않고 <br />확장하고 재사용할 수 있는 구조와 시스템으로 구축합니다.</p>
                </div>
              </div>
            </div>
            <div class="why-item">
              <div class="why-item-inner">
                <div class="why-item-head">
                  <span class="why-item-num title-ls">ADMINISTER</span>
                  <h3 class="title-ls">Direct Communication</h3>
                </div>

                <div class="why-item-cen">
                  <img src="/aseets/imgs/why-sec/why-sec-5.png" alt="why-img" />
                </div>

                <div class="why-item-bottom">
                  <p>여러 단계를 거쳐 전달하지 않고 실제 만드는 사람과 <br />프로젝트의 방향과 필요한 내용을 바로 이야기합니다.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      <section class="contact-sec" id="contact" data-header-theme="light">
        <div class="contact-bg"></div>
        <div class="contact-inner">
          <div class="left-box">
            <h2 class="title title-ls">CONTACT</h2>
            <p class="text-1">
              아이디어와 기술을 융합하여<br />
              새로운 경험을 설계합니다.
            </p>
            <p class="text-2">
              프로젝트 문의, 협업 제안, 기타 궁금한 점이 있으시다면<br />
              언제든지 연락 주세요.
            </p>
          </div>
          <div class="right-box">
<?php
            // JS 없이 제출했을 때 /inquiry.php 가 남긴 결과 메시지와 (실패 시) 입력값
            $inquiryFlash = json_decode(flash('inquiry') ?? '', true) ?: [];
            $inquiryOld = $inquiryFlash['old'] ?? [];
            $inquiryOk = !empty($inquiryFlash['ok']);
            $inquiryMessage = (string) ($inquiryFlash['message'] ?? '');
?>
            <form class="contact-form" method="post" action="/inquiry.php" novalidate data-contact-form>
              <?= csrf_field() ?>
              <!-- 봇 차단용 숨김 필드: 사람은 비워 둔다 -->
              <div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;">
                <label for="website">Website</label>
                <input type="text" id="website" name="website" value="" tabindex="-1" autocomplete="off" />
              </div>
              <div class="contact-form-track">
                <div class="form-row">
                  <label for="name">
                    Name
                    <span class="required">*</span>
                  </label>

                  <div class="input-box">
                    <input type="text" id="name" name="name" placeholder="your Name" maxlength="50" value="<?= e($inquiryOld['name'] ?? '') ?>" required />
                  </div>
                </div>

                <div class="form-row">
                  <label for="email">
                    Email
                    <span class="required">*</span>
                  </label>

                  <div class="input-box">
                    <input type="email" id="email" name="email" placeholder="your Email" maxlength="255" value="<?= e($inquiryOld['email'] ?? '') ?>" required />
                  </div>
                </div>

                <div class="form-row">
                  <label for="phone">
                    Phone
                    <span class="required">*</span>
                  </label>

                  <div class="input-box">
                    <input type="tel" id="phone" name="phone" placeholder="your Phone Number" maxlength="30" value="<?= e($inquiryOld['phone'] ?? '') ?>" required />
                  </div>
                </div>

                <div class="form-row">
                  <label for="budget"> Budget </label>

                  <div class="input-box">
                    <input type="text" id="budget" name="budget" placeholder="your Budget" maxlength="100" value="<?= e($inquiryOld['budget'] ?? '') ?>" />
                  </div>
                </div>

                <div class="form-row">
                  <label for="schedule"> Schedule </label>

                  <div class="input-box">
                    <input type="text" id="schedule" name="schedule" placeholder="your Schedule" maxlength="100" value="<?= e($inquiryOld['schedule'] ?? '') ?>" />
                  </div>
                </div>

                <div class="form-row">
                  <label for="company"> Company </label>

                  <div class="input-box">
                    <input type="text" id="company" name="company" placeholder="your Company" maxlength="100" value="<?= e($inquiryOld['company'] ?? '') ?>" />
                  </div>
                </div>

                <div class="form-row form-row-textarea">
                  <label for="details">
                    Project Details
                    <span class="required">*</span>
                  </label>

                  <div class="input-box">
                    <textarea id="details" name="details" placeholder="your message" maxlength="5000" data-lenis-prevent required><?= e($inquiryOld['details'] ?? '') ?></textarea>
                  </div>
                </div>

                <div class="form-row form-row-privacy">
                  <span class="form-label">
                    Privacy
                    <span class="required">*</span>
                  </span>

                  <div class="input-box">
                    <label class="check-box">
                      <input type="checkbox" name="privacy" value="1" required />

                      <span class="check-ui"></span>

                      <span class="check-text"> 개인정보 수집·이용에 동의합니다. </span>
                    </label>
                  </div>
                </div>

                <div class="form-submit" style="position:relative;">
                  <!-- 제출 결과 메시지: 버튼 위 여백에 겹쳐 띄워 GSAP 스크롤 길이에 영향 없게 함 -->
                  <p
                    class="contact-form-message"
                    role="status"
                    aria-live="polite"
                    data-contact-message
                    style="position:absolute;bottom:100%;left:0;right:0;margin:0 0 1.6rem;text-align:center;font-size:1.6rem;font-weight:500;line-height:1.5;color:<?= $inquiryOk ? '#9ef0b5' : '#ff8a80' ?>;"
                    <?= $inquiryMessage === '' ? 'hidden' : '' ?>
                  ><?= e($inquiryMessage) ?></p>
                  <button type="submit" class="marquee-btn">
                    <span class="marquee-text">문의하기</span>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </section>
    </main>

    <script src="/js/contact-form.js" defer></script>
<?php view('site/footer', ['isHome' => true]); ?>
