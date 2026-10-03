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

// 공통 푸터 텍스트 롤링
(() => {
  document.querySelectorAll('.footer-nav a').forEach((link) => {
    if (link.querySelector('.rolling-label')) return;

    const label = link.textContent.trim();
    link.setAttribute('aria-label', label);
    link.textContent = '';

    const clip = document.createElement('span');
    clip.className = 'rolling-label';
    clip.setAttribute('aria-hidden', 'true');

    [...label].forEach((character, index) => {
      const column = document.createElement('span');
      column.className = 'rolling-character';
      column.style.setProperty('--index', index);

      const stack = document.createElement('span');
      stack.className = 'rolling-stack';

      for (let i = 0; i < 2; i++) {
        const glyph = document.createElement('span');
        glyph.textContent = character === ' ' ? '\u00a0' : character;
        stack.append(glyph);
      }

      column.append(stack);
      clip.append(column);
    });

    link.append(clip);
  });
})();
