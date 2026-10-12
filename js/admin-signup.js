const form = document.querySelector("#signup-form");
const idInput = document.querySelector("#admin-id");
const passwordInput = document.querySelector("#password");
const confirmInput = document.querySelector("#password-confirm");
const message = document.querySelector("#form-message");

document.querySelector("#check-id").addEventListener("click", async () => {
  if (!idInput.checkValidity()) {
    idInput.reportValidity();
    return;
  }
  message.textContent = "";
  try {
    const response = await fetch(`/admin/check-id.php?${new URLSearchParams({ loginId: idInput.value.trim() })}`);
    const result = await response.json();
    message.textContent = result.message;
  } catch {
    message.textContent = "중복확인 중 오류가 발생했습니다. 다시 시도해 주세요.";
  }
});

form.addEventListener("submit", (event) => {
  message.textContent = "";

  if (!form.checkValidity()) {
    event.preventDefault();
    form.reportValidity();
    return;
  }

  if (passwordInput.value !== confirmInput.value) {
    event.preventDefault();
    confirmInput.setCustomValidity("비밀번호가 일치하지 않습니다.");
    confirmInput.reportValidity();
  }
});

confirmInput.addEventListener("input", () => {
  confirmInput.setCustomValidity("");
  message.textContent = "";
});

idInput.addEventListener("input", () => {
  message.textContent = "";
});
