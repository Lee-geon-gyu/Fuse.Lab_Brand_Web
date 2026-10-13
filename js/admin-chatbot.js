(() => {
  const form = document.querySelector("#chatbot-settings");
  const iconInput = document.querySelector("#icon-file");
  const iconPreview = document.querySelector("#icon-preview");
  const uploadName = document.querySelector("#upload-name");
  const colorInput = document.querySelector("#theme-color");
  const toast = document.querySelector("#toast");
  const buttons = form.querySelectorAll(".form-actions button");
  // PHP 페이지는 <body data-chatbot-api> 로 서버 저장, 없으면 localStorage 저장 (.html 원본과 같은 동작)
  const chatbotApi = document.body.dataset.chatbotApi;
  const csrfToken = document.body.dataset.csrf || "";
  const defaultIcon = document.body.dataset.defaultIcon || "/aseets/icons/admin/all-inbox.svg";
  const savedKey = "fuselab.chatbot.settings";
  const draftKey = chatbotApi ? "fuselab.chatbot.server-draft" : "fuselab.chatbot.draft";
  const iconTypes = ["image/png", "image/jpeg", "image/webp"];
  const defaults = {
    aiName: "",
    guideText: "",
    themeColor: "",
    tone: "friendly",
    welcomeMessage: "안녕하세요, 궁금하신 점을 편하게 물어보세요 😄",
    iconData: "",
    iconName: "",
  };
  let currentIconData = "";
  let currentIconName = "";
  let toastTimer;

  function notify(message) {
    toast.textContent = message;
    toast.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toast.hidden = true; }, 3500);
  }

  function read(key) {
    try {
      const value = JSON.parse(localStorage.getItem(key));
      return value && typeof value === "object" ? value : null;
    } catch {
      return null;
    }
  }

  function removeDraft() {
    try {
      localStorage.removeItem(draftKey);
    } catch {
      // 저장 공간을 못 쓰면 지울 임시저장도 없다
    }
  }

  function apply(settings) {
    const value = { ...defaults, ...settings };
    form.elements.aiName.value = value.aiName;
    form.elements.guideText.value = value.guideText;
    form.elements.themeColor.value = value.themeColor;
    form.elements.welcomeMessage.value = value.welcomeMessage;
    const selectedTone = form.querySelector(`input[name="tone"][value="${value.tone}"]`);
    (selectedTone || form.querySelector('input[name="tone"][value="friendly"]')).checked = true;
    currentIconData = value.iconData;
    currentIconName = value.iconName;
    iconPreview.src = currentIconData || defaultIcon;
    uploadName.textContent = currentIconName || (chatbotApi && currentIconData ? "등록된 아이콘" : "");
    uploadName.title = uploadName.textContent;
    iconInput.value = "";
    updateColorPreview();
  }

  function collect() {
    return {
      aiName: form.elements.aiName.value.trim(),
      guideText: form.elements.guideText.value.trim(),
      themeColor: colorInput.value.trim(),
      tone: form.querySelector('input[name="tone"]:checked').value,
      welcomeMessage: form.elements.welcomeMessage.value,
      iconData: currentIconData,
      iconName: currentIconName,
    };
  }

  function updateColorPreview() {
    const color = colorInput.value.trim();
    form.style.setProperty("--preview-color", /^#[0-9a-fA-F]{6}$/.test(color) ? color : "#6a00ff");
  }

  function validColor() {
    const color = colorInput.value.trim();
    if (color && !/^#[0-9a-fA-F]{6}$/.test(color)) {
      colorInput.focus();
      notify("테마 색상은 #6a00ff처럼 입력해 주세요.");
      return false;
    }
    return true;
  }

  function persist(key, successMessage) {
    if (!validColor()) return;
    try {
      localStorage.setItem(key, JSON.stringify(collect()));
      if (key === savedKey) localStorage.removeItem(draftKey);
      notify(successMessage);
    } catch {
      notify("브라우저 저장 공간을 사용할 수 없습니다. 아이콘 파일 크기를 확인해 주세요.");
    }
  }

  // ---- 서버 모드 ----
  // 서버에 저장된 값 (취소 시 이 값으로 되돌림). 첫 값은 PHP 가 채운 폼에서 읽는다
  let serverSaved = null;

  async function requestApi(body) {
    body.append("csrf_token", csrfToken);
    buttons.forEach((button) => { button.disabled = true; });
    try {
      const response = await fetch(chatbotApi, { method: "POST", body });
      const data = await response.json().catch(() => ({ ok: false }));
      if (!response.ok || !data.ok) throw new Error(data.message || "서버 요청에 실패했습니다.");
      return data;
    } finally {
      buttons.forEach((button) => { button.disabled = false; });
    }
  }

  async function saveToServer() {
    if (!validColor()) return;
    const value = collect();
    const body = new FormData();
    body.append("action", "save");
    ["aiName", "guideText", "themeColor", "tone", "welcomeMessage"].forEach((key) => body.append(key, value[key]));
    if (value.iconData.startsWith("data:")) {
      const blob = await (await fetch(value.iconData)).blob();
      body.append("icon", blob, value.iconName || "icon");
    } else if (!value.iconData && serverSaved.iconData) {
      body.append("remove_icon", "1");
    }
    try {
      const data = await requestApi(body);
      serverSaved = data.settings;
      removeDraft();
      apply(serverSaved);
      notify("챗봇 설정을 저장했습니다.");
    } catch (error) {
      notify(error.message);
    }
  }

  async function deleteFromServer() {
    const body = new FormData();
    body.append("action", "delete");
    try {
      const data = await requestApi(body);
      serverSaved = data.settings;
      removeDraft();
      apply(serverSaved);
      notify("챗봇 설정을 삭제하고 기본값으로 되돌렸습니다.");
    } catch (error) {
      notify(error.message);
    }
  }

  iconInput.addEventListener("change", () => {
    const file = iconInput.files?.[0];
    if (!file) return;
    if (!iconTypes.includes(file.type) || file.size > 1024 * 1024) {
      iconInput.value = "";
      notify("1MB 이하의 PNG, JPG, WEBP 이미지를 선택해 주세요.");
      return;
    }
    const reader = new FileReader();
    reader.onload = () => {
      currentIconData = String(reader.result);
      currentIconName = file.name;
      iconPreview.src = currentIconData;
      uploadName.textContent = currentIconName;
      uploadName.title = currentIconName;
    };
    reader.readAsDataURL(file);
  });

  colorInput.addEventListener("input", updateColorPreview);
  form.addEventListener("submit", (event) => {
    event.preventDefault();
    if (chatbotApi) saveToServer();
    else persist(savedKey, "챗봇 설정을 저장했습니다.");
  });
  document.querySelector("#draft-settings").addEventListener("click", () => {
    persist(draftKey, "임시저장했습니다.");
  });
  document.querySelector("#cancel-settings").addEventListener("click", () => {
    if (chatbotApi) {
      removeDraft();
      apply(serverSaved);
      notify("저장된 설정으로 되돌렸습니다.");
      return;
    }
    apply(read(draftKey) || read(savedKey) || defaults);
    notify("저장하지 않은 변경사항을 취소했습니다.");
  });
  document.querySelector("#delete-settings").addEventListener("click", () => {
    if (chatbotApi) {
      if (confirm("챗봇 설정을 삭제하고 기본값으로 되돌릴까요? 업로드한 아이콘도 삭제됩니다.")) deleteFromServer();
      return;
    }
    if (!confirm("저장된 챗봇 설정과 임시저장 내용을 삭제할까요?")) return;
    try {
      localStorage.removeItem(savedKey);
      localStorage.removeItem(draftKey);
      apply(defaults);
      notify("챗봇 설정을 삭제했습니다.");
    } catch {
      notify("설정을 삭제할 수 없습니다.");
    }
  });

  if (chatbotApi) {
    currentIconData = iconPreview.dataset.icon || "";
    serverSaved = collect();
    const draft = read(draftKey);
    if (draft) {
      apply(draft);
      notify("임시저장한 내용을 불러왔습니다. 취소를 누르면 저장된 설정으로 돌아갑니다.");
    } else {
      apply(serverSaved);
    }
  } else {
    apply(read(draftKey) || read(savedKey) || defaults);
  }

  // ---- AI 연동 (PHP 페이지 전용). 키는 서버에만 저장하고 화면에는 가린 값만 보인다 ----
  const aiForm = document.querySelector("#chatbot-ai");
  if (chatbotApi && aiForm) {
    const aiStatus = document.querySelector("#ai-status");
    const aiKeyInput = aiForm.elements.apiKey;
    const aiDeleteButton = document.querySelector("#delete-ai-key");

    const showAi = (ai) => {
      aiStatus.textContent = ai.configured
        ? `연결됨 · 키 ${ai.maskedKey}`
        : "미설정 · 규칙 기반 자동 응답을 사용 중입니다.";
      aiKeyInput.value = "";
      aiKeyInput.placeholder = ai.configured ? "변경할 때만 새 키를 입력" : "sk-ant-...";
      aiForm.elements.model.value = ai.model;
      aiDeleteButton.hidden = !ai.configured;
    };

    aiForm.addEventListener("submit", async (event) => {
      event.preventDefault();
      const body = new FormData();
      body.append("action", "save_ai");
      body.append("apiKey", aiKeyInput.value.trim());
      body.append("model", aiForm.elements.model.value.trim());
      try {
        showAi((await requestApi(body)).ai);
        notify("AI 연동 설정을 저장했습니다.");
      } catch (error) {
        notify(error.message);
      }
    });

    aiDeleteButton.addEventListener("click", async () => {
      if (!confirm("API 키를 삭제할까요? 챗봇은 규칙 기반 자동 응답으로 돌아갑니다.")) return;
      const body = new FormData();
      body.append("action", "delete_ai_key");
      try {
        showAi((await requestApi(body)).ai);
        notify("API 키를 삭제했습니다.");
      } catch (error) {
        notify(error.message);
      }
    });
  }
})();
