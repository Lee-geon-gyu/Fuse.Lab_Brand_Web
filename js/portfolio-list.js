// 포트폴리오 목록(portfolio.php): 분류 탭 필터 + 더보기(+) 버튼.
// 서버가 첫 화면을 그리고, 이 스크립트는 ?partial=1 JSON 으로 받은 항목 HTML 을 갈아끼우거나 이어 붙인다.
// JS 가 없어도 분류 탭은 GET 폼 제출로 동작한다.
(() => {
  const root = document.querySelector("[data-portfolio-list]");
  if (!root) return;

  const rows = root.querySelector("[data-portfolio-rows]");
  const empty = root.querySelector("[data-portfolio-empty]");
  const moreButton = root.querySelector("[data-portfolio-more]");
  const filterButtons = [...root.querySelectorAll(".filter-list [data-category]")];
  const endpoint = root.dataset.endpoint || "/portfolio.php";

  let category = root.dataset.category || "";
  let page = Number(root.dataset.page) || 1;
  let requestId = 0;

  async function fetchPage(targetCategory, targetPage) {
    const params = new URLSearchParams({ partial: "1", page: String(targetPage) });
    if (targetCategory) params.set("category", targetCategory);
    const response = await fetch(`${endpoint}?${params}`, { headers: { Accept: "application/json" } });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return response.json();
  }

  function syncUrl() {
    const params = new URLSearchParams();
    if (category) params.set("category", category);
    if (page > 1) params.set("page", String(page));
    const query = params.toString();
    history.replaceState(null, "", query ? `${location.pathname}?${query}` : location.pathname);
  }

  function setMore(hasMore) {
    moreButton.hidden = !hasMore;
  }

  function setActive(nextCategory) {
    filterButtons.forEach((button) => {
      const active = button.dataset.category === nextCategory;
      button.classList.toggle("active", active);
      button.setAttribute("aria-pressed", String(active));
    });
  }

  async function showCategory(nextCategory) {
    const current = ++requestId;
    setActive(nextCategory);
    try {
      const data = await fetchPage(nextCategory, 1);
      if (current !== requestId) return;
      category = nextCategory;
      page = 1;
      rows.innerHTML = data.html;
      empty.hidden = data.count > 0;
      setMore(data.hasMore);
      syncUrl();
    } catch (error) {
      if (current !== requestId) return;
      setActive(category);
      alert("포트폴리오를 불러오지 못했습니다. 잠시 후 다시 시도해 주세요.");
    }
  }

  async function loadMore() {
    const current = ++requestId;
    moreButton.setAttribute("aria-busy", "true");
    try {
      const data = await fetchPage(category, page + 1);
      if (current !== requestId) return;
      page += 1;
      rows.insertAdjacentHTML("beforeend", data.html);
      setMore(data.hasMore);
      syncUrl();
    } catch (error) {
      if (current !== requestId) return;
      alert("포트폴리오를 더 불러오지 못했습니다. 잠시 후 다시 시도해 주세요.");
    } finally {
      moreButton.removeAttribute("aria-busy");
    }
  }

  filterButtons.forEach((button) => {
    button.addEventListener("click", (event) => {
      event.preventDefault();
      const nextCategory = button.dataset.category;
      if (nextCategory === category && page === 1) return;
      showCategory(nextCategory);
    });
  });

  moreButton.addEventListener("click", loadMore);
})();
