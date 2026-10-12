// 관리자 견적문의 (/admin/inquiries.php): 서버 렌더링 화면의 보조 동작만 담당
//  - 상태 필터 변경 시 바로 검색, 삭제 전 확인, 결과 토스트 자동 숨김
(() => {
  document.querySelectorAll("select[data-auto-submit]").forEach((select) => {
    select.addEventListener("change", () => {
      const form = select.form;
      if (!form) return;
      if (typeof form.requestSubmit === "function") form.requestSubmit();
      else form.submit();
    });
  });

  document.querySelectorAll("form[data-confirm]").forEach((form) => {
    form.addEventListener("submit", (event) => {
      if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
  });

  const toast = document.getElementById("inquiry-toast");
  if (toast && !toast.hidden) {
    window.setTimeout(() => {
      toast.hidden = true;
    }, 3000);
  }
})();
