(() => {
  const storageKey = "fuselab.board.posts";
  const flashKey = "fuselab.board.flash";
  const editorUrl = "admin-post-edit.html";
  const pageSize = 13;
  const pageGroupSize = 8;
  const template = document.querySelector("#post-row-template");
  const rows = document.querySelector("#post-rows");
  const pagination = document.querySelector("#pagination");
  const count = document.querySelector("#post-count");
  const pageSummary = document.querySelector("#page-summary");
  const selectPage = document.querySelector("#select-page");
  const categoryFilter = document.querySelector("#category-filter");
  const searchForm = document.querySelector("#search-form");
  const searchScope = document.querySelector("#search-scope");
  const searchQuery = document.querySelector("#search-query");
  const visibleOnly = document.querySelector("#visible-only");
  const toast = document.querySelector("#board-toast");

  let posts = loadPosts();
  let currentPage = 1;
  let appliedQuery = "";
  let pagePosts = [];
  let toastTimer;
  const selectedIds = new Set();

  function seedPosts() {
    // Repeated sample entries keep all 22 designed pages navigable without a server.
    return Array.from({ length: 286 }, (_, index) => ({
      id: `sample-${index + 1}`,
      no: 1,
      title: "2026년 하반기 가맹 설명회 일정 안내",
      content: "2026년 하반기 가맹 설명회 일정 안내드립니다. 자세한 일정과 참가 방법을 확인해 주세요.",
      category: "notice",
      author: "김윤영",
      date: "09-05",
      views: 1842,
      visible: true,
    }));
  }

  function loadPosts() {
    try {
      const stored = JSON.parse(localStorage.getItem(storageKey));
      return Array.isArray(stored) ? stored : seedPosts();
    } catch {
      return seedPosts();
    }
  }

  function savePosts() {
    try {
      localStorage.setItem(storageKey, JSON.stringify(posts));
      return true;
    } catch {
      notify("브라우저 저장 공간을 사용할 수 없어 이번 변경은 새로고침 후 유지되지 않습니다.");
      return false;
    }
  }

  function notify(message) {
    toast.textContent = message;
    toast.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toast.hidden = true; }, 3500);
  }

  function filteredPosts() {
    const category = categoryFilter.value;
    const query = appliedQuery.toLocaleLowerCase();
    const scope = searchScope.value;

    return posts.filter((post) => {
      if (category !== "all" && post.category !== category) return false;
      if (visibleOnly.checked && !post.visible) return false;
      if (!query) return true;

      const values = {
        title: post.title,
        content: post.content,
        author: post.author,
      };
      const haystack = scope === "all"
        ? `${values.title} ${values.content}`
        : values[scope];
      return String(haystack || "").toLocaleLowerCase().includes(query);
    });
  }

  // A reusable table-row component, cloned for every post and every page.
  function createPostRow(post) {
    const row = template.content.firstElementChild.cloneNode(true);
    const checkbox = row.querySelector(".row-check");
    const titleButton = row.querySelector(".post-title");

    checkbox.dataset.postId = post.id;
    checkbox.checked = selectedIds.has(post.id);
    checkbox.setAttribute("aria-label", `${post.title} 선택`);
    row.querySelector(".post-number").textContent = post.no;
    titleButton.dataset.postId = post.id;
    titleButton.textContent = post.title;
    titleButton.setAttribute("aria-label", `${post.title} 열기`);
    row.querySelector(".post-author").textContent = post.author;
    row.querySelector(".post-date").textContent = post.date;
    row.querySelector(".post-views").textContent = Number(post.views).toLocaleString("ko-KR");
    row.querySelector(".post-visibility").textContent = post.visible ? "노출" : "숨김";
    return row;
  }

  function createPageButton(label, page, options = {}) {
    const button = document.createElement("button");
    button.type = "button";
    button.textContent = label;
    if (options.className) button.className = options.className;
    button.disabled = Boolean(options.disabled);
    if (options.current) {
      button.setAttribute("aria-current", "page");
    } else if (options.ariaLabel) {
      button.setAttribute("aria-label", options.ariaLabel);
    } else if (!options.disabled) {
      button.setAttribute("aria-label", `${page}페이지로 이동`);
    }
    button.addEventListener("click", () => {
      currentPage = page;
      render();
    });
    return button;
  }

  function renderPagination(totalPages) {
    pagination.replaceChildren();
    const start = Math.floor((currentPage - 1) / pageGroupSize) * pageGroupSize + 1;
    const end = Math.min(start + pageGroupSize - 1, totalPages);

    pagination.append(createPageButton("<<", Math.max(1, start - pageGroupSize), {
      disabled: start === 1,
      className: "pagination__group--previous",
      ariaLabel: "이전 페이지 묶음",
    }));
    pagination.append(createPageButton("이전", Math.max(1, currentPage - 1), {
      disabled: currentPage === 1,
    }));

    for (let page = start; page <= end; page += 1) {
      pagination.append(createPageButton(String(page), page, {
        current: page === currentPage,
      }));
    }

    pagination.append(createPageButton("다음", Math.min(totalPages, currentPage + 1), {
      disabled: currentPage === totalPages,
    }));
    pagination.append(createPageButton(">>", Math.min(totalPages, start + pageGroupSize), {
      disabled: end === totalPages,
      className: "pagination__group--next",
      ariaLabel: "다음 페이지 묶음",
    }));
  }

  function updateSelectPage() {
    const selectedOnPage = pagePosts.filter((post) => selectedIds.has(post.id)).length;
    selectPage.checked = pagePosts.length > 0 && selectedOnPage === pagePosts.length;
    selectPage.indeterminate = selectedOnPage > 0 && selectedOnPage < pagePosts.length;
    selectPage.disabled = pagePosts.length === 0;
  }

  function render() {
    const results = filteredPosts();
    const totalPages = Math.max(1, Math.ceil(results.length / pageSize));
    currentPage = Math.min(Math.max(1, currentPage), totalPages);
    pagePosts = results.slice((currentPage - 1) * pageSize, currentPage * pageSize);

    const fragment = document.createDocumentFragment();
    if (pagePosts.length) {
      pagePosts.forEach((post) => fragment.append(createPostRow(post)));
    } else {
      const row = document.createElement("tr");
      row.className = "empty-row";
      const cell = document.createElement("td");
      cell.colSpan = 7;
      cell.textContent = "게시글이 없습니다.";
      row.append(cell);
      fragment.append(row);
    }
    rows.replaceChildren(fragment);
    count.textContent = results.length.toLocaleString("ko-KR");
    pageSummary.textContent = `${currentPage} / ${totalPages}페이지`;
    updateSelectPage();
    renderPagination(totalPages);
  }

  // 편집 화면이 같은 저장소를 읽으므로 샘플 데이터도 먼저 저장해 둠
  function openEditor(post = null) {
    if (!savePosts()) return;
    const params = post
      ? new URLSearchParams({ id: post.id })
      : new URLSearchParams({ category: categoryFilter.value === "all" ? "notice" : categoryFilter.value });
    location.href = `${editorUrl}?${params}`;
  }

  function showFlash() {
    try {
      const message = sessionStorage.getItem(flashKey);
      sessionStorage.removeItem(flashKey);
      if (message) notify(message);
    } catch {}
  }

  categoryFilter.addEventListener("change", () => {
    currentPage = 1;
    selectedIds.clear();
    render();
  });

  visibleOnly.addEventListener("change", () => {
    currentPage = 1;
    selectedIds.clear();
    render();
  });

  searchForm.addEventListener("submit", (event) => {
    event.preventDefault();
    appliedQuery = searchQuery.value.trim();
    currentPage = 1;
    selectedIds.clear();
    render();
  });

  selectPage.addEventListener("change", () => {
    pagePosts.forEach((post) => {
      if (selectPage.checked) selectedIds.add(post.id);
      else selectedIds.delete(post.id);
    });
    render();
  });

  rows.addEventListener("change", (event) => {
    const checkbox = event.target.closest(".row-check");
    if (!checkbox) return;
    if (checkbox.checked) selectedIds.add(checkbox.dataset.postId);
    else selectedIds.delete(checkbox.dataset.postId);
    updateSelectPage();
  });

  rows.addEventListener("click", (event) => {
    const titleButton = event.target.closest(".post-title");
    if (!titleButton) return;
    const post = posts.find((item) => item.id === titleButton.dataset.postId);
    if (!post) return;
    post.views += 1;
    openEditor(post);
  });

  function changeSelected(action) {
    if (!selectedIds.size) {
      notify("먼저 게시글을 선택해 주세요.");
      return;
    }

    const selectedCount = selectedIds.size;
    if (action === "delete") {
      if (!confirm(`선택한 게시글 ${selectedCount}건을 삭제할까요?`)) return;
      posts = posts.filter((post) => !selectedIds.has(post.id));
    } else {
      posts.forEach((post) => {
        if (selectedIds.has(post.id)) post.visible = action === "show";
      });
    }

    selectedIds.clear();
    savePosts();
    render();
    notify(action === "delete"
      ? `${selectedCount}건을 삭제했습니다.`
      : `${selectedCount}건을 ${action === "show" ? "노출" : "숨김"} 처리했습니다.`);
  }

  document.querySelector("#show-selected").addEventListener("click", () => changeSelected("show"));
  document.querySelector("#hide-selected").addEventListener("click", () => changeSelected("hide"));
  document.querySelector("#delete-selected").addEventListener("click", () => changeSelected("delete"));
  document.querySelector("#new-post").addEventListener("click", () => openEditor());

  render();
  showFlash();
})();
