(() => {
  const loginForm = document.querySelector("#login-form");
  const loginId = document.querySelector("#login-id");
  const loginPassword = document.querySelector("#login-password");
  const loginFeedback = document.querySelector("#login-feedback");
  const dialog = document.querySelector("#recovery-dialog");
  const recoveryFeedback = document.querySelector("#recovery-feedback");
  const idTab = document.querySelector("#id-tab");
  const passwordTab = document.querySelector("#password-tab");
  const idPanel = document.querySelector("#id-panel");
  const passwordPanel = document.querySelector("#password-panel");

  loginForm.addEventListener("submit", (event) => {
    loginFeedback.textContent = "";
    if (!loginId.value.trim()) {
      event.preventDefault();
      loginId.focus();
      loginFeedback.textContent = "ID 또는 이메일을 입력해 주세요.";
      return;
    }
    if (!loginPassword.value) {
      event.preventDefault();
      loginPassword.focus();
      loginFeedback.textContent = "비밀번호를 입력해 주세요.";
    }
  });

  document.querySelector("#open-recovery").addEventListener("click", () => {
    recoveryFeedback.textContent = "";
    dialog.showModal();
  });
  document.querySelector("#close-recovery").addEventListener("click", () => dialog.close());
  dialog.addEventListener("click", (event) => {
    if (event.target === dialog) dialog.close();
  });

  function activateTab(tab) {
    const showId = tab === idTab;
    idTab.setAttribute("aria-selected", String(showId));
    passwordTab.setAttribute("aria-selected", String(!showId));
    idPanel.hidden = !showId;
    passwordPanel.hidden = showId;
    recoveryFeedback.textContent = "";
    tab.focus();
  }

  idTab.addEventListener("click", () => activateTab(idTab));
  passwordTab.addEventListener("click", () => activateTab(passwordTab));
  for (const tab of [idTab, passwordTab]) {
    tab.addEventListener("keydown", (event) => {
      if (event.key !== "ArrowLeft" && event.key !== "ArrowRight") return;
      event.preventDefault();
      activateTab(tab === idTab ? passwordTab : idTab);
    });
  }

  for (const panel of [idPanel, passwordPanel]) {
    panel.addEventListener("submit", (event) => {
      event.preventDefault();
      recoveryFeedback.textContent = "";
      if (!panel.checkValidity()) {
        panel.reportValidity();
        return;
      }
      recoveryFeedback.textContent = "계정 찾기는 인증 서버 연결 후 사용할 수 있습니다.";
    });
  }
})();
