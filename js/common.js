(() => {
  const header = document.getElementById('header');
  const sections = document.querySelectorAll('[data-header-theme]');

  // 헤더 하단 라인에 걸친 섹션의 테마를 헤더에 적용
  const update = () => {
    const line = header.offsetHeight / 2;
    for (const s of sections) {
      const r = s.getBoundingClientRect();
      if (r.top <= line && r.bottom > line) {
        header.dataset.theme = s.dataset.headerTheme;
        return;
      }
    }
  };

  addEventListener('scroll', update, { passive: true });
  addEventListener('resize', update);
  update();
})();
