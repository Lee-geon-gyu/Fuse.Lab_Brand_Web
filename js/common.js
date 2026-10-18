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

// 문의 폼 개인정보 수집·이용 안내 아코디언
(() => {
  document.querySelectorAll('.contact-inner .check-text span[aria-controls]').forEach((trigger) => {
    const panel = document.getElementById(trigger.getAttribute('aria-controls'));
    if (!panel) return;

    const track = trigger.closest('.contact-form-track');
    const inner = panel.querySelector('.privacy-details-inner');
    if (!track || !inner) return;

    const measure = () => {
      track.style.setProperty('--privacy-height', `${inner.offsetHeight}px`);
    };
    measure();
    track.classList.add('has-privacy-accordion');
    new ResizeObserver(measure).observe(inner);

    // 여백을 미리 넣지 않고 실제 폼 높이에 맞춰 이동량만 갱신한다.
    // 기존 타임라인의 진행률을 사용하므로 페이지 refresh나 강제 스크롤이 필요 없다.
    const connectScroll = () => {
      const section = trigger.closest('.contact-sec');
      const rightBox = section?.querySelector('.right-box');
      const scrollTrigger = window.ScrollTrigger?.getAll().find((item) => item.trigger === section);
      if (!rightBox || !scrollTrigger?.animation || !window.gsap) return;

      const animation = scrollTrigger.animation;
      const setY = window.gsap.quickSetter(track, 'y', 'px');
      const syncScroll = () => {
        const distance = Math.max(0, track.scrollHeight - rightBox.clientHeight);
        setY(-distance * animation.progress());
      };

      animation.eventCallback('onUpdate', syncScroll);
      new ResizeObserver(syncScroll).observe(panel);
      syncScroll();
    };
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', connectScroll, { once: true });
    } else {
      connectScroll();
    }

    const toggle = (event) => {
      // label 안의 문구를 눌러도 동의 체크 상태는 바꾸지 않는다.
      event.preventDefault();
      const isOpen = trigger.getAttribute('aria-expanded') !== 'true';
      trigger.setAttribute('aria-expanded', String(isOpen));
      panel.setAttribute('aria-hidden', String(!isOpen));
      panel.inert = !isOpen;
      panel.classList.toggle('is-open', isOpen);
    };

    trigger.addEventListener('click', toggle);
    trigger.addEventListener('keydown', (event) => {
      if ((event.key === 'Enter' || event.key === ' ') && !event.repeat) toggle(event);
    });
  });
})();
