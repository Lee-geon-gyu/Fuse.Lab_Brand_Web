(() => {
  const form = document.querySelector("#chatbot-settings");
  const iconInput = document.querySelector("#icon-file");
  const iconPreview = document.querySelector("#icon-preview");
  const uploadName = document.querySelector("#upload-name");
  const colorInput = document.querySelector("#theme-color");
  const toast = document.querySelector("#toast");
  const defaultIcon = "/aseets/icons/admin/all-inbox.svg";
  const savedKey = "fuselab.chatbot.settings";
  const draftKey = "fuselab.chatbot.draft";
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
    uploadName.textContent = currentIconName;
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

  iconInput.addEventListener("change", () => {
    const file = iconInput.files?.[0];
    if (!file) return;
    if (!file.type.startsWith("image/") || file.size > 1024 * 1024) {
      iconInput.value = "";
      notify("1MB 이하의 이미지 파일을 선택해 주세요.");
      return;
    }
    const reader = new FileReader();
    reader.onload = () => {
      currentIconData = String(reader.result);
      currentIconName = file.name;
      iconPreview.src = currentIconData;
      uploadName.textContent = currentIconName;
    };
    reader.readAsDataURL(file);
  });

  colorInput.addEventListener("input", updateColorPreview);
  form.addEventListener("submit", (event) => {
    event.preventDefault();
    persist(savedKey, "챗봇 설정을 저장했습니다.");
  });
  document.querySelector("#draft-settings").addEventListener("click", () => {
    persist(draftKey, "임시저장했습니다.");
  });
  document.querySelector("#cancel-settings").addEventListener("click", () => {
    apply(read(draftKey) || read(savedKey) || defaults);
    notify("저장하지 않은 변경사항을 취소했습니다.");
  });
  document.querySelector("#delete-settings").addEventListener("click", () => {
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

  apply(read(draftKey) || read(savedKey) || defaults);
})();
