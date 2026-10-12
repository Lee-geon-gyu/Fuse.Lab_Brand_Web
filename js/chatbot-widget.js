// 사용자 챗봇 위젯: 규칙 기반 응답 (키워드 → 안내 + 링크). 설정·연락처는 #chatbot 의 data-* 로 받는다
(() => {
  const root = document.querySelector("#chatbot");
  if (!root) return;

  const panel = root.querySelector(".chatbot__panel");
  const launcher = root.querySelector(".chatbot__launcher");
  const closeButton = root.querySelector(".chatbot__close");
  const log = root.querySelector(".chatbot__log");
  const form = root.querySelector(".chatbot__form");
  const input = root.querySelector("#chatbot-input");
  const data = root.dataset;
  const tone = ["friendly", "calm", "warm", "concise"].includes(data.tone) ? data.tone : "friendly";
  let tracked = false;

  const links = {
    contact: { label: "견적 문의하기", href: data.contactUrl },
    portfolio: { label: "포트폴리오 보기", href: data.portfolioUrl },
    services: { label: "서비스 소개 보기", href: data.servicesUrl },
    email: { label: "메일 보내기", href: `mailto:${data.email}` },
    phone: { label: "전화 걸기", href: `tel:${data.phone.replace(/[^0-9+]/g, "")}` },
  };
  const contactLines = `\n· 이메일: ${data.email}\n· 전화: ${data.phone}\n· 주소: ${data.address}`;

  // 의도별 말투 문장 (관리자 화면 말투 예시의 어미를 따른다)
  const replies = {
    quote: {
      links: ["contact"],
      friendly: "견적은 프로젝트 범위와 일정에 따라 달라져요. 메인의 견적 문의 폼에 내용을 남겨 주시면 담당자가 확인하고 알려드릴게요 😄",
      calm: "견적은 프로젝트 범위와 일정에 따라 산정됩니다. 메인의 견적 문의 폼에 내용을 남겨 주시면 담당자가 검토 후 안내드리겠습니다.",
      warm: "어떤 프로젝트를 준비하고 계신가요? 견적 문의 폼에 내용을 남겨 주시면 담당자가 함께 볼게요. 폼으로 같이 가 볼까요?",
      concise: "견적은 범위·일정에 따라 다릅니다. 견적 문의 폼으로 접수하시면 안내드립니다.",
    },
    portfolio: {
      links: ["portfolio"],
      friendly: "fuse.lab이 만든 작업은 포트폴리오 페이지에서 한눈에 보실 수 있어요. 바로 가는 링크 알려드릴게요 😄",
      calm: "fuse.lab의 작업 사례는 포트폴리오 페이지에서 확인하실 수 있습니다. 아래 링크로 안내드리겠습니다.",
      warm: "저희가 만든 작업들이 포트폴리오에 모여 있어요. 어떤 프로젝트가 있는지 같이 살펴볼까요?",
      concise: "작업 사례는 포트폴리오 페이지에서 안내드립니다.",
    },
    services: {
      links: ["services", "contact"],
      friendly: "fuse.lab은 웹서비스·MVP, 브랜드 웹사이트, 자동화·내부 도구, UX·UI 디자인, 콘텐츠·커뮤니티, 디지털 경험을 만들어요. 자세한 내용은 서비스 소개에서 알려드릴게요 😄",
      calm: "fuse.lab은 웹서비스·MVP, 브랜드 웹사이트, 자동화·내부 도구, UX·UI 디자인, 콘텐츠·커뮤니티, 디지털 경험 분야를 진행합니다. 서비스 소개에서 자세히 안내드리겠습니다.",
      warm: "아이디어를 실제로 작동하는 서비스로 만드는 일을 해요. 웹서비스·MVP부터 브랜드 웹사이트, UX·UI 디자인까지 있어요. 서비스 소개를 같이 살펴볼까요?",
      concise: "서비스: 웹서비스·MVP, 브랜드 웹사이트, 자동화·내부 도구, UX·UI 디자인, 콘텐츠·커뮤니티, 디지털 경험. 서비스 소개에서 안내드립니다.",
    },
    contactInfo: {
      links: ["email", "phone", "contact"],
      friendly: `fuse.lab 연락처 알려드릴게요 😄${contactLines}`,
      calm: `fuse.lab 연락처를 안내드리겠습니다.${contactLines}`,
      warm: `편하신 방법으로 연락 주세요. 연락처를 같이 살펴볼까요?${contactLines}`,
      concise: `연락처를 안내드립니다.${contactLines}`,
    },
    greeting: {
      links: [],
      friendly: "안녕하세요! 견적, 포트폴리오, 서비스, 연락처 중 궁금한 걸 물어보시면 바로 알려드릴게요 😄",
      calm: "안녕하세요. 견적, 포트폴리오, 서비스, 연락처에 관해 물어보시면 차분히 안내드리겠습니다.",
      warm: "반가워요! 견적, 포트폴리오, 서비스, 연락처 중에 어떤 게 궁금하신지 같이 살펴볼까요?",
      concise: "안녕하세요. 견적·포트폴리오·서비스·연락처 문의를 안내드립니다.",
    },
    fallback: {
      links: ["contact"],
      friendly: "그 질문은 제가 아직 답하기 어려워요. 견적, 포트폴리오, 서비스, 연락처를 물어보시거나 견적 문의 폼에 남겨 주시면 담당자가 알려드릴게요 😄",
      calm: "해당 질문에는 바로 답변드리기 어렵습니다. 견적 문의 폼에 남겨 주시면 담당자가 확인 후 안내드리겠습니다.",
      warm: "그 부분은 담당자와 직접 이야기하시는 게 좋겠어요. 견적 문의 폼에 남겨 주시면 함께 고민할게요. 같이 가 볼까요?",
      concise: "답변할 수 없는 질문입니다. 견적 문의 폼으로 접수하시면 안내드립니다.",
    },
  };

  // 앞에 있는 규칙이 먼저 적용된다 (예: "전화 문의" → 연락처)
  const rules = [
    ["contactInfo", /연락처|전화|이메일|메일|주소|위치|오시는|찾아가/],
    ["quote", /견적|문의|가격|비용|금액|얼마|예산/],
    ["portfolio", /포트폴리오|작업|사례|레퍼런스|portfolio/],
    ["services", /서비스|mvp|브랜딩|브랜드|디자인|개발|무엇을|뭐 하/],
    ["greeting", /안녕|하이|반가|hello|^hi\b|헬로/],
  ];

  function intentOf(text) {
    const normalized = text.toLowerCase();
    return rules.find(([, pattern]) => pattern.test(normalized))?.[0] || "fallback";
  }

  function scrollToEnd() {
    log.scrollTop = log.scrollHeight;
  }

  // 모든 문구는 textContent 로만 넣는다 (사용자 입력이 HTML 로 해석되지 않도록)
  function addMessage(text, from, linkKeys = []) {
    const message = document.createElement("p");
    message.className = `chatbot__msg chatbot__msg--${from}`;
    message.textContent = text;
    log.append(message);
    if (linkKeys.length) {
      const actions = document.createElement("div");
      actions.className = "chatbot__links";
      linkKeys.forEach((key) => {
        const link = document.createElement("a");
        link.href = links[key].href;
        link.textContent = links[key].label;
        actions.append(link);
      });
      log.append(actions);
    }
    scrollToEnd();
  }

  function ask(question) {
    const text = question.trim();
    if (!text) return;
    addMessage(text, "user");
    const reply = replies[intentOf(text)];
    const typing = document.createElement("p");
    typing.className = "chatbot__msg chatbot__msg--bot chatbot__msg--typing";
    typing.setAttribute("aria-label", "답변 작성 중");
    typing.textContent = "···";
    log.append(typing);
    scrollToEnd();
    setTimeout(() => {
      typing.remove();
      addMessage(reply[tone], "bot", reply.links);
    }, 400);
  }

  function track() {
    if (tracked || !data.openUrl) return;
    tracked = true;
    const body = new FormData();
    body.append("csrf_token", data.csrf || "");
    fetch(data.openUrl, { method: "POST", body }).catch(() => {});
  }

  function setOpen(open) {
    panel.hidden = !open;
    root.classList.toggle("is-open", open);
    launcher.setAttribute("aria-expanded", String(open));
    launcher.setAttribute("aria-label", open ? "챗봇 닫기" : launcher.dataset.openLabel);
    if (open) {
      track();
      scrollToEnd();
      input.focus({ preventScroll: true });
    }
  }

  launcher.dataset.openLabel = launcher.getAttribute("aria-label");
  launcher.addEventListener("click", () => setOpen(panel.hidden));
  closeButton.addEventListener("click", () => {
    setOpen(false);
    launcher.focus();
  });
  root.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !panel.hidden) {
      setOpen(false);
      launcher.focus();
    }
  });
  root.querySelectorAll(".chatbot__quick button").forEach((button) => {
    button.addEventListener("click", () => ask(button.textContent));
  });
  form.addEventListener("submit", (event) => {
    event.preventDefault();
    ask(input.value);
    input.value = "";
  });
  // 같은 페이지 안 링크(#contact 등)로 이동하면 패널을 닫아 본문이 보이게 한다
  log.addEventListener("click", (event) => {
    const link = event.target.closest("a");
    if (link && link.getAttribute("href").startsWith("#")) setOpen(false);
  });

  // 메인의 관리자 바(.admin-bar, 우하단 고정)가 있으면 그 위로 올린다
  const adminBar = document.querySelector(".admin-bar");
  if (adminBar) {
    const raise = () => {
      root.style.setProperty("--chatbot-offset", `${adminBar.getBoundingClientRect().height + 12}px`);
    };
    raise();
    if ("ResizeObserver" in window) new ResizeObserver(raise).observe(adminBar);
  }
})();
