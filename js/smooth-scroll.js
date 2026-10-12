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

  const isReload = performance.getEntriesByType("navigation")[0]?.type === "reload";
  const startScroll = () => {
    lenis.start();
    ScrollTrigger.refresh();
    // Restore the hero after GSAP measures the layout and the loader unlocks scroll.
    if (isReload) lenis.scrollTo(0, { immediate: true });
  };

  if (document.body.classList.contains("is-loading")) {
    lenis.stop();
    window.addEventListener("loader:done", startScroll, { once: true });
  }

  window.addEventListener("load", () => {
    if (document.body.classList.contains("is-loading")) ScrollTrigger.refresh();
    else startScroll();
  }, { once: true });
  document.fonts?.ready.then(() => ScrollTrigger.refresh());
})();
