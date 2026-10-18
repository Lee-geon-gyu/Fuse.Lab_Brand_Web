(() => {
  const loader = document.getElementById('loader');
  if (!loader) return;

  const MIN_SHOW = 2400; // 로더 문구 등장 연출이 끝나는 시간
  const MAX_WAIT = 10000; // 폰트·이미지 CDN 이 늦어도 이 시간이 지나면 닫음
  const start = performance.now();

  // 로딩 중엔 스크롤바를 보이게 둔 채(css/common.css) 입력만 막고 맨 위에 고정
  const SCROLL_KEYS = new Set(['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End', ' ']);
  const blockInput = (e) => { if (e.cancelable) e.preventDefault(); };
  const blockKeys = (e) => { if (SCROLL_KEYS.has(e.key)) e.preventDefault(); };
  const pinTop = () => { if (window.scrollY !== 0) window.scrollTo({ top: 0, behavior: 'instant' }); };
  const lockScroll = (on) => {
    const method = on ? 'addEventListener' : 'removeEventListener';
    window[method]('wheel', blockInput, { passive: false });
    window[method]('touchmove', blockInput, { passive: false });
    window[method]('keydown', blockKeys);
    window[method]('scroll', pinTop, { passive: true });
  };

  const finish = () => {
    lockScroll(false);
    loader.style.display = 'none';
    window.dispatchEvent(new Event('loader:done'));
    document.body.classList.remove('is-loading');
  };

  if (/[?&]noloader(&|=|$)/.test(location.search)) { finish(); return; }

  lockScroll(true);
  pinTop();

  const hide = () => {
    loader.classList.add('is-done');
    document.body.classList.add('hero-intro');
    let closed = false;
    const close = () => { if (!closed) { closed = true; finish(); } };
    loader.addEventListener('transitionend', (e) => {
      if (e.target === loader && (e.propertyName === 'clip-path' || e.propertyName === 'opacity')) close();
    });
    setTimeout(close, 1600);
  };

  const loaded = new Promise((resolve) => {
    if (document.readyState === 'complete') resolve();
    else window.addEventListener('load', resolve, { once: true });
  });
  // 처음 보이는 이미지는 로더 뒤에서 미리 디코딩해 두어 첫 스크롤에서 끊기지 않게 함
  const decodeImages = () => Promise.all(
    [...document.querySelectorAll('img[loading="eager"], img[fetchpriority="high"]')]
      .map((img) => img.decode().catch(() => {})),
  );
  const nextFrames = () => new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));

  // load 시점엔 화면에 쓰인 폰트 요청이 모두 시작돼 있으므로 fonts.ready 가 그 폰트들을 기다림.
  // 폰트·이미지가 자리 잡은 뒤 GSAP 위치를 로더 뒤에서 한 번 계산해 두고 닫아, 닫힌 직후 다시 계산하며 끊기는 일이 없게 함
  const ready = loaded
    .then(() => Promise.all([document.fonts ? document.fonts.ready : null, decodeImages()]))
    .then(() => {
      if (window.ScrollTrigger) window.ScrollTrigger.refresh();
      return nextFrames();
    });

  Promise.race([ready, new Promise((resolve) => setTimeout(resolve, MAX_WAIT))])
    .then(() => setTimeout(hide, Math.max(0, MIN_SHOW - (performance.now() - start))));
})();
