// 서브페이지 편집 (admin/subpage-edit.php): 탭 전환, 코드 칸 Tab 들여쓰기, 저장 안 한 변경 경고
(() => {
  const form = document.querySelector("#subpage-form");
  if (!form) return;

  const tabs = [...form.querySelectorAll("[data-tab]")];
  const panels = [...form.querySelectorAll("[data-panel]")];
  const storageKey = `fuselab.subpage-tab:${location.search}`;
  let dirty = false;

  function show(name) {
    const target = tabs.some((tab) => tab.dataset.tab === name) ? name : tabs[0].dataset.tab;
    tabs.forEach((tab) => tab.setAttribute("aria-selected", String(tab.dataset.tab === target)));
    panels.forEach((panel) => { panel.hidden = panel.dataset.panel !== target; });
    try {
      sessionStorage.setItem(storageKey, target);
    } catch {
      // 저장 공간을 못 쓰면 첫 탭부터
    }
  }

  tabs.forEach((tab) => tab.addEventListener("click", () => show(tab.dataset.tab)));

  // 숨은 탭의 필수 칸이 비어 있으면 그 탭을 열어 브라우저 안내가 보이게
  form.addEventListener("invalid", (event) => {
    const panel = event.target.closest("[data-panel]");
    if (panel?.hidden) show(panel.dataset.panel);
  }, true);

  // 코드 칸에서 Tab = 공백 2칸 (Shift+Tab 은 기본 동작으로 포커스 이동)
  form.querySelectorAll(".subpage-code").forEach((textarea) => {
    textarea.addEventListener("keydown", (event) => {
      if (event.key !== "Tab" || event.shiftKey) return;
      event.preventDefault();
      textarea.setRangeText("  ", textarea.selectionStart, textarea.selectionEnd, "end");
      dirty = true;
    });
  });

  form.addEventListener("input", () => { dirty = true; });
  form.addEventListener("submit", () => { dirty = false; });
  // 백업 되돌리기 (form="restore-form" 버튼): 확인 후 제출, 편집 중 내용은 버림
  form.querySelectorAll("[data-confirm]").forEach((button) => {
    button.addEventListener("click", (event) => {
      if (!confirm(button.dataset.confirm)) {
        event.preventDefault();
        return;
      }
      dirty = false;
    });
  });
  window.addEventListener("beforeunload", (event) => {
    if (dirty) event.preventDefault();
  });

  const toast = document.querySelector("#subpage-toast");
  if (toast) setTimeout(() => { toast.hidden = true; }, 3000);

  let saved = null;
  try {
    saved = sessionStorage.getItem(storageKey);
  } catch {
    saved = null;
  }
  show(saved);
})();
