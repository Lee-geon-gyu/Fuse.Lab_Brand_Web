// 메인 견적문의 폼(index.php #contact): fetch 로 /inquiry.php 에 제출하고 결과를 폼 안에 표시한다.
// JS 가 없으면 폼이 일반 POST 로 제출되고 서버가 /#contact 로 되돌아오며 메시지를 보여준다.
(() => {
  const form = document.querySelector("[data-contact-form]");
  if (!form || !window.fetch || !window.FormData) return;

  const message = form.querySelector("[data-contact-message]");
  const submitButton = form.querySelector('button[type="submit"]');
  const COLORS = { ok: "#9ef0b5", error: "#ff8a80" };
  let sending = false;

  function showMessage(text, ok) {
    if (!message) return;
    message.textContent = text;
    message.style.color = ok ? COLORS.ok : COLORS.error;
    message.hidden = text === "";
  }

  function setSending(value) {
    sending = value;
    if (!submitButton) return;
    submitButton.disabled = value;
    submitButton.setAttribute("aria-busy", value ? "true" : "false");
    submitButton.style.opacity = value ? "0.6" : "";
  }

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (sending) return;

    setSending(true);
    showMessage("전송 중입니다…", true);

    let result = null;
    try {
      const response = await fetch(form.action, {
        method: "POST",
        body: new FormData(form),
        headers: { Accept: "application/json" },
        credentials: "same-origin",
      });
      result = await response.json();
    } catch (error) {
      result = { ok: false, message: "전송 중 오류가 발생했습니다. 잠시 후 다시 시도해 주세요." };
    }

    setSending(false);
    showMessage(result.message || "", Boolean(result.ok));

    if (result.ok) {
      form.reset();
      return;
    }

    // 첫 번째 오류 항목으로 포커스 (GSAP 고정 스크롤이 튀지 않게 스크롤은 막음)
    const firstField = Object.keys(result.errors || {})[0];
    const target = firstField ? form.elements.namedItem(firstField) : null;
    if (target && typeof target.focus === "function") {
      target.focus({ preventScroll: true });
    }
  });
})();
