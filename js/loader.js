(() => {
  const loader = document.getElementById('loader');
  if (!loader) return;

  const MIN_SHOW = 2400;
  const start = performance.now();

  const finish = () => {
    loader.style.display = 'none';
    window.dispatchEvent(new Event('loader:done'));
    document.body.classList.remove('is-loading');
  };

  if (/[?&]noloader(&|=|$)/.test(location.search)) { finish(); return; }

  const hide = () => {
    loader.classList.add('is-done');
    let closed = false;
    const close = () => { if (!closed) { closed = true; finish(); } };
    loader.addEventListener('transitionend', (e) => { if (e.target === loader && e.propertyName === 'opacity') close(); });
    setTimeout(close, 1200);
  };

  const onLoad = () => setTimeout(hide, Math.max(0, MIN_SHOW - (performance.now() - start)));

  if (document.readyState === 'complete') onLoad();
  else window.addEventListener('load', onLoad, { once: true });
})();
