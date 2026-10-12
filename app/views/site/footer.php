<?php
/**
 * 사용자 footer + 챗봇 위젯 + 공통 스크립트 + </body></html>
 * @var bool $isHome
 */
$home = $isHome ? '' : '/';
// 연락처: footer 와 챗봇 응답이 같은 값을 쓴다
$siteContact = [
    'email' => 'hello@fuselab.example',
    'phone' => '02-0000-2026',
    'address' => '서울특별시 성동구 성수이로 00길 00, 4F',
];
?>
    <footer class="footer" data-header-theme="dark">
      <div class="footer-grid">
        <div class="left-box">
          <span><?= e($siteContact['email']) ?></span>
          <span><?= e($siteContact['phone']) ?></span>
          <span><?= e($siteContact['address']) ?></span>
        </div>

        <div class="cen-box">
          <a href="<?= $home ?>#hero">
            <img src="/aseets/imgs/footer/footer_logo.png" alt="fuse.lab" />
          </a>
          <p>Ideas into working products.</p>
        </div>

        <div class="right-box">
          <ul class="footer-nav">
            <li><a href="<?= $home ?>#hero">home</a></li>
            <li><a href="<?= $home ?>#gyu-main">portfolio</a></li>
            <li><a href="<?= $home ?>#gyu-mvp">Services</a></li>
            <li><a href="<?= $home ?>#why-us">Why Us</a></li>
            <li><a href="<?= $home ?>#contact">Contact</a></li>
          </ul>

          <ul class="footer-social">
            <li><a href="#"><img src="/aseets/icons/footer/ins_ico.svg" alt="" /></a></li>
            <li><a href="#"><img src="/aseets/icons/footer/you_ico.svg" alt="" /></a></li>
            <li><a href="#"><img src="/aseets/icons/footer/ka_ico.svg" alt="" /></a></li>
            <li><a href="#"><img src="/aseets/icons/footer/git_ico.svg" alt="" /></a></li>
          </ul>
        </div>
      </div>
    </footer>

<?php view('site/chatbot', ['isHome' => $isHome, 'contact' => $siteContact]); ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="/js/common.js"></script>
    <script src="/js/loader-ring.js"></script>
    <script src="/js/loader.js"></script>
    <script src="/js/hero-flux.js"></script>
    <script src="/js/divider.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3/dist/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3/dist/ScrollTrigger.min.js"></script>
<?php if ($isHome): ?>
    <script src="https://cdn.jsdelivr.net/npm/lenis@1.3.26/dist/lenis.min.js"></script>
<?php endif; ?>
    <script src="/script.js"></script>
<?php if ($isHome): ?>
    <script src="/js/smooth-scroll.js"></script>
<?php endif; ?>
    <script src="/js/chatbot-widget.js"></script>
  </body>
</html>
