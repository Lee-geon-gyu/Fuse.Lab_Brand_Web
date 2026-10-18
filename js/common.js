(() => {
  const header = document.getElementById('header');
  const sections = document.querySelectorAll('[data-header-theme]');

  // 헤더 하단 라인에 걸친 섹션의 테마를 헤더에 적용
  const updateTheme = () => {
    const line = header.offsetHeight / 2;
    for (const s of sections) {
      const r = s.getBoundingClientRect();
      if (r.top <= line && r.bottom > line) {
        header.dataset.theme = s.dataset.headerTheme;
        return;
      }
    }
  };

  // 20px 넘게 내려간 상태에서 아래로 스크롤하면 접고, 위로 스크롤하면 다시 보임
  const HIDE_AFTER = 20;
  let lastY = Math.max(0, scrollY);
  const updateVisibility = () => {
    const y = Math.max(0, scrollY);
    if (y <= HIDE_AFTER || y < lastY) header.classList.remove('is-hidden');
    else if (y > lastY) header.classList.add('is-hidden');
    lastY = y;
  };

  addEventListener('scroll', () => { updateTheme(); updateVisibility(); }, { passive: true });
  addEventListener('resize', updateTheme);
  updateTheme();
})();

// 공통 헤더·푸터 내비게이션 텍스트 롤링
(() => {
  document.querySelectorAll('.gnb__list a, .footer-nav a').forEach((link) => {
    if (link.querySelector('.rolling-label')) return;

    const label = link.textContent.trim();
    link.classList.add('rolling-link');
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
