<?php
/**
 * 사용자 footer + 공통 스크립트 + </body></html>
 * @var bool $isHome
 */
$home = $isHome ? '' : '/';
?>
    <footer class="footer" data-header-theme="dark">
      <div class="footer-grid">
        <div class="left-box">
          <span>hello@fuselab.example</span>
          <span>02-0000-2026</span>
          <span>서울특별시 성동구 성수이로 00길 00, 4F</span>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="/js/common.js"></script>
    <script src="/js/loader-ring.js"></script>
    <script src="/js/loader.js"></script>
    <script src="/js/hero-flux.js"></script>
    <script src="/js/divider.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3/dist/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3/dist/ScrollTrigger.min.js"></script>
    <script src="/script.js"></script>
  </body>
</html>
