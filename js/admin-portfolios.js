// 관리자 포트폴리오 목록(admin/portfolios.php)·편집(admin/portfolio-edit.php) 보조 동작.
// 저장·삭제 등은 모두 서버 폼 POST 로 처리하고, 여기서는 편의 기능만 붙인다.
(() => {
  // 분류 필터: 고르면 바로 이동
  document.querySelectorAll("form[data-auto-submit] select").forEach((select) => {
    select.addEventListener("change", () => select.form.submit());
  });

  // 전체 선택 체크박스
  const selectAll = document.querySelector("[data-select-all]");
  const rowChecks = [...document.querySelectorAll(".row-check")];
  if (selectAll) {
    const sync = () => {
      const checked = rowChecks.filter((check) => check.checked).length;
      selectAll.checked = checked > 0 && checked === rowChecks.length;
      selectAll.indeterminate = checked > 0 && checked < rowChecks.length;
    };
    selectAll.addEventListener("change", () => {
      rowChecks.forEach((check) => {
        check.checked = selectAll.checked;
      });
      sync();
    });
    rowChecks.forEach((check) => check.addEventListener("change", sync));
  }

  // 삭제 확인
  document.querySelectorAll("[data-confirm]").forEach((button) => {
    button.addEventListener("click", (event) => {
      if (!confirm(button.dataset.confirm)) event.preventDefault();
    });
  });

  // 이미지 선택 시 미리보기
  document.querySelectorAll("input[type=file][data-preview]").forEach((input) => {
    const preview = document.querySelector(input.dataset.preview);
    if (!preview) return;
    const original = preview.getAttribute("src");
    const originalHidden = preview.hidden;
    let objectUrl = null;
    input.addEventListener("change", () => {
      if (objectUrl) URL.revokeObjectURL(objectUrl);
      objectUrl = null;
      const file = input.files[0];
      if (file) {
        objectUrl = URL.createObjectURL(file);
        preview.src = objectUrl;
        preview.hidden = false;
      } else {
        preview.src = original;
        preview.hidden = originalHidden;
      }
    });
  });

  // 결과 메시지 자동 숨김
  const toast = document.querySelector(".toast[data-auto-hide]");
  if (toast) setTimeout(() => (toast.hidden = true), 3000);
})();
