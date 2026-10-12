const form = document.querySelector("#signup-form");
const idInput = document.querySelector("#admin-id");
const passwordInput = document.querySelector("#password");
const confirmInput = document.querySelector("#password-confirm");
const message = document.querySelector("#form-message");

document.querySelector("#check-id").addEventListener("click", () => {
  if (!idInput.value.trim()) {
    idInput.reportValidity();
    return;
  }
  message.textContent = "중복확인은 서버 연결 후 사용할 수 있습니다.";
});

form.addEventListener("submit", (event) => {
  event.preventDefault();
  message.textContent = "";

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  if (passwordInput.value !== confirmInput.value) {
    confirmInput.setCustomValidity("비밀번호가 일치하지 않습니다.");
    confirmInput.reportValidity();
    return;
  }

  message.textContent = "회원가입은 서버 연결 후 사용할 수 있습니다.";
});

confirmInput.addEventListener("input", () => {
  confirmInput.setCustomValidity("");
  message.textContent = "";
});
