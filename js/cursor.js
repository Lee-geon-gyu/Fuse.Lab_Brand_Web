// 사용자 페이지 마우스 반응: 커스텀 커서(링크·버튼 위에서 커짐) + 마그네틱 버튼(마우스 쪽으로 끌림).
// 마우스가 있는 기기(hover + 정밀 포인터)에서만, 동작 줄이기 설정이면 끔. 스타일은 css/cursor.css
(() => {
  const finePointer = window.matchMedia("(hover: hover) and (pointer: fine)");
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
  if (!finePointer.matches || reducedMotion.matches) return;

  const INTERACTIVE = "a[href], button:not(:disabled), [role='button'], label, summary, select";
  const TEXT_FIELD = "input:not([type='checkbox']):not([type='radio']):not([type='submit']):not([type='button']), textarea, [contenteditable='true']";
  // 끌리는 버튼. transform 은 GSAP 등장 연출이 쓰므로 CSS translate 속성만 건드림
  const MAGNETIC = ".marquee-btn, .plus-btn, .chatbot__launcher";
  const MAGNET_STRENGTH = 0.3; // 버튼 중심에서 마우스까지 거리 대비 이동 비율
  const MAGNET_MAX = 14; // 최대 이동(px)
  const EASE = 0.2; // 프레임마다 목표값에 다가가는 비율 (커서·버튼 공통)

  const cursor = document.createElement("div");
  cursor.className = "fx-cursor";
  cursor.setAttribute("aria-hidden", "true");
  document.body.append(cursor);
  document.documentElement.classList.add("fx-cursor-on");

  const pointer = { x: -100, y: -100, tx: -100, ty: -100 };
  // 끌리는 중이거나 제자리로 돌아가는 중인 버튼 → { x, y, tx, ty }
  const magnets = new Map();
  let activeMagnet = null;
  let frame = 0;

  const approach = (state) => {
    state.x += (state.tx - state.x) * EASE;
    state.y += (state.ty - state.y) * EASE;
    if (Math.abs(state.tx - state.x) + Math.abs(state.ty - state.y) < 0.1) {
      state.x = state.tx;
      state.y = state.ty;
      return false;
    }
    return true;
  };

  const render = () => {
    let moving = approach(pointer);
    cursor.style.transform = `translate3d(${pointer.x}px, ${pointer.y}px, 0)`;
    magnets.forEach((state, element) => {
      const stillMoving = approach(state);
      element.style.translate = state.x || state.y ? `${state.x}px ${state.y}px` : "";
      if (stillMoving) moving = true;
      else if (element !== activeMagnet && !state.x && !state.y) magnets.delete(element);
    });
    frame = moving ? window.requestAnimationFrame(render) : 0;
  };

  const requestRender = () => {
    if (!frame) frame = window.requestAnimationFrame(render);
  };

  const releaseMagnet = () => {
    if (!activeMagnet) return;
    const state = magnets.get(activeMagnet);
    if (state) {
      state.tx = 0;
      state.ty = 0;
    }
    activeMagnet = null;
  };

  const pullMagnet = (element, event) => {
    if (activeMagnet !== element) releaseMagnet();
    activeMagnet = element;
    let state = magnets.get(element);
    if (!state) {
      state = { x: 0, y: 0, tx: 0, ty: 0 };
      magnets.set(element, state);
    }
    // 이미 끌린 만큼(state.x/y)을 빼서 원래 자리 기준으로 계산
    const rect = element.getBoundingClientRect();
    const limit = (value) => Math.max(-MAGNET_MAX, Math.min(MAGNET_MAX, value * MAGNET_STRENGTH));
    state.tx = limit(event.clientX - (rect.left - state.x + rect.width / 2));
    state.ty = limit(event.clientY - (rect.top - state.y + rect.height / 2));
  };

  document.addEventListener("pointermove", (event) => {
    if (event.pointerType !== "mouse") return;
    pointer.tx = event.clientX;
    pointer.ty = event.clientY;

    const target = event.target instanceof Element ? event.target : null;
    const textField = target?.closest(TEXT_FIELD);
    cursor.classList.add("is-visible");
    cursor.classList.toggle("is-text", Boolean(textField));
    cursor.classList.toggle("is-hover", !textField && Boolean(target?.closest(INTERACTIVE)));

    const magnetic = target?.closest(MAGNETIC);
    if (magnetic) pullMagnet(magnetic, event);
    else releaseMagnet();
    requestRender();
  }, { passive: true });

  document.addEventListener("pointerdown", () => cursor.classList.add("is-down"));
  document.addEventListener("pointerup", () => cursor.classList.remove("is-down"));
  document.documentElement.addEventListener("pointerleave", () => {
    cursor.classList.remove("is-visible");
    releaseMagnet();
    requestRender();
  });
})();
