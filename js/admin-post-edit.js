(() => {
  const storageKey = "fuselab.board.posts";
  const draftKey = "fuselab.board.drafts";
  const flashKey = "fuselab.board.flash";
  const boardUrl = "admin-board.html";

  const form = document.querySelector("#editor-form");
  const titleInput = document.querySelector("#post-title");
  const contentInput = document.querySelector("#post-content");
  const categorySelect = document.querySelector("#post-category");
  const fileInput = document.querySelector("#post-files");
  const fileList = document.querySelector("#file-list");
  const deleteButton = document.querySelector("#delete-post");
  const toast = document.querySelector("#board-toast");

  const editingId = new URLSearchParams(location.search).get("id");
  const draftSlot = editingId || "new";
  let posts = readJson(storageKey, []);
  let files = [];
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

  function readForm() {
    return {
      title: titleInput.value.trim(),
      content: contentInput.value.trim(),
      category: categorySelect.value,
      visible: form.elements.visible.value === "true",
      files: [...files],
    };
  }

  function fillForm(data) {
    titleInput.value = data.title || "";
    contentInput.value = data.content || "";
    categorySelect.value = data.category || "notice";
    categorySelect.dispatchEvent(new Event("change", { bubbles: true }));
    form.elements.visible.value = String(data.visible ?? true);
    files = Array.isArray(data.files) ? [...data.files] : [];
    renderFiles();
  }

  const snapshot = () => JSON.stringify(readForm());

  function renderFiles() {
    fileList.replaceChildren(
      ...files.map((name, index) => {
        const item = document.createElement("li");
        const label = document.createElement("span");
        const remove = document.createElement("button");
        label.textContent = name;
        remove.type = "button";
        remove.textContent = "×";
        remove.setAttribute("aria-label", `${name} 첨부 삭제`);
        remove.addEventListener("click", () => {
          files.splice(index, 1);
          renderFiles();
        });
        item.append(label, remove);
        return item;
      }),
    );
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

  const editingPost = editingId ? posts.find((post) => post.id === editingId) : null;

  if (editingId && !editingPost) {
    leaveWith("게시글을 찾을 수 없습니다.");
    return;
  }

  if (editingPost) {
    document.title = "게시글 수정 | fuse.lab";
    document.querySelector("#editor-title").textContent = "게시글 수정";
    deleteButton.hidden = false;
    fillForm(editingPost);
  } else {
    const params = new URLSearchParams(location.search);
    fillForm({ category: params.get("category") || "notice" });
  }
  savedSnapshot = snapshot();

  const draft = readJson(draftKey, {})[draftSlot];
  if (draft) {
    fillForm(draft);
    notify("임시저장된 내용을 불러왔습니다.");
  }

  fileInput.addEventListener("change", () => {
    const names = [...fileInput.files].map((file) => file.name);
    files = [...new Set([...files, ...names])];
    fileInput.value = "";
    renderFiles();
  });

  [titleInput, contentInput].forEach((input) => {
    input.addEventListener("input", () => input.removeAttribute("aria-invalid"));
  });

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

  deleteButton.addEventListener("click", () => {
    if (!confirm("이 게시글을 삭제할까요?")) return;
    posts = posts.filter((post) => post.id !== editingId);
    if (!writeJson(storageKey, posts)) return;
    removeDraft();
    leaveWith("게시글을 삭제했습니다.");
  });

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    const data = validate();
    if (!data) return;

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
  });

  window.addEventListener("beforeunload", (event) => {
    if (snapshot() !== savedSnapshot) event.preventDefault();
  });
})();
