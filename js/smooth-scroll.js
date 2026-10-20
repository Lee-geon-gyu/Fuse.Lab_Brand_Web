(() => {
  if (typeof Lenis === "undefined" || !window.gsap || !window.ScrollTrigger) return;

  gsap.registerPlugin(ScrollTrigger);

  const lenis = new Lenis({
    autoRaf: false,
    smoothWheel: true,
    lerp: 0.07,
    wheelMultiplier: 1,
    syncTouch: false,
    anchors: true,
    respectReducedMotion: true,
  });

  window.lenis = lenis;

  // Use one clock for smooth scrolling and the pinned GSAP sections.
  lenis.on("scroll", ScrollTrigger.update);
  gsap.ticker.add((time) => lenis.raf(time * 1000));
  gsap.ticker.lagSmoothing(0);
  ScrollTrigger.addEventListener("refresh", () => lenis.resize());

  // 새로고침 시 맨 위로: 메인만 (head.php 가 메인에서만 scrollRestoration 을 manual 로 둠). 서브페이지는 브라우저 위치 복원 유지
  const isReload = performance.getEntriesByType("navigation")[0]?.type === "reload";
  const resetScroll = () => {
    if (isReload && history.scrollRestoration === "manual") lenis.scrollTo(0, { immediate: true });
  };

  if (document.body.classList.contains("is-loading")) {
    // 위치 계산(ScrollTrigger.refresh)은 js/loader.js 가 로더를 닫기 전에 끝내 둠. 닫힌 뒤엔 스크롤만 풀어 줌
    lenis.stop();
    window.addEventListener("loader:done", () => {
      lenis.start();
      resetScroll();
    }, { once: true });
  } else {
    // ?noloader: 로더 없이 바로 보이므로 폰트가 바뀐 뒤 위치를 다시 계산
    window.addEventListener("load", resetScroll, { once: true });
    document.fonts?.ready.then(() => ScrollTrigger.refresh());
  }
})();
