(() => {
  const storageKey = "fuselab.board.posts";
  const draftKey = "fuselab.board.drafts";
  const flashKey = "fuselab.board.flash";
  const params = new URLSearchParams(location.search);
  // ?return=/ 처럼 같은 사이트 경로가 오면 저장·취소·삭제 뒤 그 주소로 돌아감 (메인의 관리자 수정 링크)
  const returnPath = params.get("return") || "";
  const boardUrl = /^\/(?![/\\])/.test(returnPath)
    ? returnPath
    : document.body.dataset.boardUrl || "admin-board.html";
  // PHP 페이지는 <body data-posts-api> 로 서버 저장, .html 원본은 localStorage
  const postsApi = document.body.dataset.postsApi;
  const csrfToken = document.body.dataset.csrf || "";
  const imageMaxBytes = 10 * 1024 * 1024;

  const form = document.querySelector("#editor-form");
  const titleInput = document.querySelector("#post-title");
  const contentInput = document.querySelector("#post-content");
  const categorySelect = document.querySelector("#post-category");
  const fileInput = document.querySelector("#post-files");
  const fileList = document.querySelector("#file-list");
  const deleteButton = document.querySelector("#delete-post");
  const submitButton = form.querySelector('[type="submit"]');
  const toast = document.querySelector("#board-toast");
  // 포트폴리오 전용 입력 (PHP 화면에만 있음)
  const eyebrowInput = document.querySelector("#post-eyebrow");
  const portfolioSelect = document.querySelector("#post-portfolio");
  const imageInput = document.querySelector("#post-image");
  const imagePreview = document.querySelector("#image-preview");
  const boardOnlyFields = [...document.querySelectorAll("[data-board-only]")];

  const editingId = params.get("id");
  const draftSlot = editingId || "new";
  let posts = postsApi ? [] : readJson(storageKey, []);
  // localStorage 모드: 파일 이름 문자열 / 서버 모드: { id?, name, url?, file? }
  let files = [];
  let removedFileIds = [];
  let imageFile = null;
  let imageUrl = "";
  let imageObjectUrl = "";
  let editingPost = null;
  let savedSnapshot = "";
  let toastTimer;

  function readJson(key, fallback) {
    try {
      return JSON.parse(localStorage.getItem(key)) ?? fallback;
    } catch {
      return fallback;
    }
  }

  function writeJson(key, value) {
    try {
      localStorage.setItem(key, JSON.stringify(value));
      return true;
    } catch {
      notify("브라우저 저장 공간을 사용할 수 없어 저장하지 못했습니다.");
      return false;
    }
  }

  async function requestApi(params, body = null) {
    const url = `${postsApi}?${new URLSearchParams(params)}`;
    const response = await fetch(url, body ? { method: "POST", body } : {});
    const data = await response.json().catch(() => ({ ok: false }));
    if (!response.ok || !data.ok) throw new Error(data.message || "서버 요청에 실패했습니다.");
    return data;
  }

  function apiForm(action) {
    const body = new FormData();
    body.append("csrf_token", csrfToken);
    body.append("action", action);
    return body;
  }

  function notify(message) {
    toast.textContent = message;
    toast.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toast.hidden = true; }, 3500);
  }

  function leaveWith(message) {
    try {
      sessionStorage.setItem(flashKey, message);
    } catch {}
    savedSnapshot = snapshot();
    location.href = boardUrl;
  }

  const fileName = (file) => (typeof file === "string" ? file : file.name);

  function readForm() {
    return {
      title: titleInput.value.trim(),
      content: contentInput.value.trim(),
      category: categorySelect.value,
      eyebrow: eyebrowInput ? eyebrowInput.value.trim() : "",
      portfolioId: portfolioSelect ? portfolioSelect.value : "",
      visible: form.elements.visible.value === "true",
      files: files.map(fileName),
      image: imageFile ? imageFile.name : imageUrl,
    };
  }

  function fillForm(data) {
    titleInput.value = data.title || "";
    contentInput.value = data.content || "";
    // 없는 게시판(삭제됨 등)이면 첫 게시판
    categorySelect.value = data.category || "";
    if (categorySelect.selectedIndex < 0) categorySelect.selectedIndex = 0;
    categorySelect.dispatchEvent(new Event("change", { bubbles: true }));
    if (eyebrowInput) eyebrowInput.value = data.eyebrow || "";
    if (portfolioSelect) {
      portfolioSelect.value = data.portfolioId || "";
      // 숨김→삭제 등으로 목록에 없는 id 면 연결 없음으로 표시
      if (portfolioSelect.selectedIndex < 0) portfolioSelect.value = "";
      portfolioSelect.dispatchEvent(new Event("change", { bubbles: true }));
    }
    form.elements.visible.value = String(data.visible ?? true);
    files = Array.isArray(data.files) ? [...data.files] : [];
    imageUrl = data.image || "";
    imageFile = null;
    renderFiles();
    renderImage();
  }

  const snapshot = () => JSON.stringify(readForm());

  function renderFiles() {
    fileList.replaceChildren(
      ...files.map((file, index) => {
        const name = fileName(file);
        const item = document.createElement("li");
        const label = document.createElement(file.url ? "a" : "span");
        const remove = document.createElement("button");
        label.textContent = name;
        label.title = name;
        if (file.url) {
          label.href = file.url;
          label.download = name;
        }
        remove.type = "button";
        remove.textContent = "×";
        remove.setAttribute("aria-label", `${name} 첨부 삭제`);
        remove.addEventListener("click", () => {
          if (file.id) removedFileIds.push(file.id);
          files.splice(index, 1);
          renderFiles();
        });
        item.append(label, remove);
        return item;
      }),
    );
  }

  function renderImage() {
    if (!imagePreview) return;
    if (imageObjectUrl) URL.revokeObjectURL(imageObjectUrl);
    imageObjectUrl = imageFile ? URL.createObjectURL(imageFile) : "";
    const src = imageObjectUrl || imageUrl;
    imagePreview.hidden = !src;
    if (src) imagePreview.src = src;
    else imagePreview.removeAttribute("src");
  }

  function toggleBoardFields() {
    boardOnlyFields.forEach((field) => {
      field.hidden = field.dataset.boardOnly !== categorySelect.value;
    });
  }

  function validate() {
    const data = readForm();
    const missing = [
      [titleInput, data.title],
      [contentInput, data.content],
    ].filter(([, value]) => !value);

    [titleInput, contentInput].forEach((input) => input.removeAttribute("aria-invalid"));
    missing.forEach(([input]) => input.setAttribute("aria-invalid", "true"));
    if (missing.length) {
      missing[0][0].focus();
      notify("제목과 내용을 입력해 주세요.");
      return null;
    }
    if (imageInput && data.category === "portfolio" && !data.image) {
      imageInput.focus();
      notify("포트폴리오는 배경 이미지를 선택해 주세요.");
      return null;
    }
    return data;
  }

  function nextNumber() {
    return Math.max(0, ...posts.map((post) => Number(post.no) || 0)) + 1;
  }

  function todayLabel() {
    const now = new Date();
    return `${String(now.getMonth() + 1).padStart(2, "0")}-${String(now.getDate()).padStart(2, "0")}`;
  }

  function removeDraft() {
    const drafts = readJson(draftKey, {});
    delete drafts[draftSlot];
    writeJson(draftKey, drafts);
  }

  async function saveToServer(data) {
    const body = apiForm("save");
    if (editingPost) body.append("id", editingPost.id);
    body.append("category", data.category);
    body.append("title", data.title);
    body.append("eyebrow", data.eyebrow);
    body.append("portfolio_id", data.portfolioId);
    body.append("content", data.content);
    body.append("visible", String(data.visible));
    if (imageFile) body.append("image", imageFile);
    files.filter((file) => file.file).forEach((file) => body.append("files[]", file.file));
    removedFileIds.forEach((id) => body.append("remove_files[]", id));

    submitButton.disabled = true;
    try {
      await requestApi({}, body);
      removeDraft();
      leaveWith(editingPost ? "게시글을 수정했습니다." : "게시글을 등록했습니다.");
    } catch (error) {
      notify(error.message);
    } finally {
      submitButton.disabled = false;
    }
  }

  function saveToStorage(data) {
    if (editingPost) {
      Object.assign(editingPost, data);
    } else {
      posts.unshift({
        id: `post-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
        no: nextNumber(),
        ...data,
        author: "관리자",
        date: todayLabel(),
        views: 0,
      });
    }

    if (!writeJson(storageKey, posts)) return;
    removeDraft();
    leaveWith(editingPost ? "게시글을 수정했습니다." : "게시글을 등록했습니다.");
  }

  categorySelect.addEventListener("change", toggleBoardFields);

  fileInput.addEventListener("change", () => {
    const picked = [...fileInput.files];
    if (postsApi) {
      const known = new Set(files.map(fileName));
      picked.forEach((file) => {
        if (!known.has(file.name)) files.push({ name: file.name, file });
      });
    } else {
      files = [...new Set([...files, ...picked.map((file) => file.name)])];
    }
    fileInput.value = "";
    renderFiles();
  });

  imageInput?.addEventListener("change", () => {
    const [file] = imageInput.files;
    imageInput.value = "";
    if (!file) return;
    if (file.size > imageMaxBytes) {
      notify("이미지는 10MB 이하만 올릴 수 있습니다.");
      return;
    }
    imageFile = file;
    renderImage();
  });

  [titleInput, contentInput].forEach((input) => {
    input.addEventListener("input", () => input.removeAttribute("aria-invalid"));
  });

  // 임시저장은 브라우저에 글 내용만 저장 (서버 모드에서 새 파일·이미지는 저장되지 않음)
  document.querySelector("#draft-post").addEventListener("click", () => {
    const drafts = readJson(draftKey, {});
    drafts[draftSlot] = { ...readForm(), savedAt: Date.now() };
    if (writeJson(draftKey, drafts)) notify("임시저장했습니다.");
  });

  document.querySelector("#cancel-post").addEventListener("click", () => {
    if (snapshot() !== savedSnapshot && !confirm("작성 중인 내용이 저장되지 않습니다. 나갈까요?")) return;
    savedSnapshot = snapshot();
    location.href = boardUrl;
  });

  deleteButton.addEventListener("click", async () => {
    if (!confirm("이 게시글을 삭제할까요?")) return;
    if (postsApi) {
      const body = apiForm("delete");
      body.append("id", editingPost.id);
      try {
        await requestApi({}, body);
      } catch (error) {
        notify(error.message);
        return;
      }
    } else {
      posts = posts.filter((post) => post.id !== editingId);
      if (!writeJson(storageKey, posts)) return;
    }
    removeDraft();
    leaveWith("게시글을 삭제했습니다.");
  });

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    const data = validate();
    if (!data) return;
    if (postsApi) saveToServer(data);
    else saveToStorage(data);
  });

  window.addEventListener("beforeunload", (event) => {
    if (snapshot() !== savedSnapshot) event.preventDefault();
  });

  async function init() {
    if (editingId) {
      try {
        editingPost = postsApi
          ? (await requestApi({ action: "get", id: editingId })).post
          : posts.find((post) => post.id === editingId) ?? null;
      } catch {
        editingPost = null;
      }
      if (!editingPost) {
        leaveWith("게시글을 찾을 수 없습니다.");
        return;
      }
    }

    if (editingPost) {
      document.title = "게시글 수정 | fuse.lab";
      document.querySelector("#editor-title").textContent = "게시글 수정";
      deleteButton.hidden = false;
      fillForm(editingPost);
    } else {
      // 새 글: ?category= 로 게시판 미리 선택
      fillForm({ category: params.get("category") || "" });
    }
    savedSnapshot = snapshot();

    const draft = readJson(draftKey, {})[draftSlot];
    if (draft) {
      // 서버 모드에서는 첨부·이미지는 서버에 있는 그대로 둠
      fillForm(postsApi ? { ...draft, files, image: imageUrl } : draft);
      notify("임시저장된 내용을 불러왔습니다.");
    }
  }

  init();
})();
