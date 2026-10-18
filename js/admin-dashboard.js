(() => {
  const PAGE_SIZE = 5;
  const PAGE_GROUP_SIZE = 10;

  // PHP 페이지는 <body data-dashboard-api> 로 서버 데이터, .html 원본은 목업 데이터
  const { dashboardApi, inquiryUrl, postEditUrl, today } = document.body.dataset;

  const STATUS_BADGES = {
    "접수 완료": "green",
    "검토 중": "yellow",
    "처리 완료": "blue",
    "답변 대기": "",
    반려: "pink",
  };
  // .html 목업 분류 (서버 모드에서는 API 의 boards 를 탭으로 사용)
  const CATEGORY_BADGES = {
    공지사항: "green",
    프로젝트: "purple",
    디자인: "pink",
    개발: "blue",
    운영: "",
  };
  // 서버 게시판 키별 색. 목록에 없는 키(관리자가 새로 만든 게시판)는 기본 회색 배지
  const BOARD_BADGES = {
    portfolio: "pink",
    ...CATEGORY_BADGES,
  };
  const DONUT_LABEL = dashboardApi ? "최근 30일 로그인 계정 비율" : "탈퇴 회원 비율";

  const monthInput = document.querySelector("#month-input");
  const monthLabel = document.querySelector("#month-label");
  const weekSelect = document.querySelector("#week-select");
  const visitorBars = document.querySelector("#visitor-bars");
  const visitorAxis = document.querySelectorAll(".bar-chart__axis span");
  const postTabs = document.querySelector("#post-tabs");

  const numberFormat = new Intl.NumberFormat("ko-KR");
  const pad = (value) => String(value).padStart(2, "0");

  async function requestApi(params) {
    const response = await fetch(`${dashboardApi}?${new URLSearchParams(params)}`);
    const data = await response.json().catch(() => ({ ok: false }));
    if (!response.ok || !data.ok) throw new Error(data.message || "서버 요청에 실패했습니다.");
    return data;
  }

  // 목업 데이터(.html 원본 전용): 월 키 기반 시드로 매번 같은 값 생성
  function seededRandom(seed) {
    let state = seed;
    return () => {
      state = (state * 1103515245 + 12345) % 2147483648;
      return state / 2147483648;
    };
  }

  const DESIGN_FIXTURE = {
    "2026-09": {
      chatbotRate: 7.4,
      visitorTotal: 6974,
      firstWeek: [483, 309, 189, 387, 349, 77, 232],
      members: { total: 5877, month: 4485, today: 85, rate: 6.9 },
    },
  };

  function getMockMonthStats(year, month) {
    const key = `${year}-${pad(month)}`;
    const random = seededRandom(year * 100 + month);
    const daysInMonth = new Date(year, month, 0).getDate();
    const daily = Array.from({ length: daysInMonth }, () => Math.round(60 + random() * 480));
    const fixture = DESIGN_FIXTURE[key];

    if (fixture) {
      daily.splice(0, fixture.firstWeek.length, ...fixture.firstWeek);
      return { ...fixture, daily };
    }

    return {
      chatbotRate: Math.round((3 + random() * 10) * 10) / 10,
      visitorTotal: daily.reduce((sum, value) => sum + value, 0),
      daily,
      members: {
        total: Math.round(4000 + random() * 3000),
        month: Math.round(500 + random() * 4000),
        today: Math.round(random() * 120),
        rate: Math.round(random() * 150) / 10,
      },
    };
  }

  async function loadMonthStats(year, month) {
    if (!dashboardApi) return getMockMonthStats(year, month);
    const { stats, members } = await requestApi({ action: "stats", month: `${year}-${pad(month)}` });
    return {
      chatbotRate: stats.chatbotRate,
      visitorTotal: stats.visitorTotal,
      daily: stats.daily,
      members: { total: members.total, month: members.month, today: members.today, rate: members.activeRate },
    };
  }

  // 축 눈금 3칸: 최대값 / 3 을 1·2·5 × 10^n 단위로 올림
  function axisStep(maxValue) {
    const raw = Math.max(maxValue, 1) / 3;
    const magnitude = 10 ** Math.floor(Math.log10(raw));
    const normalized = raw / magnitude;
    const nice = normalized <= 1 ? 1 : normalized <= 2 ? 2 : normalized <= 5 ? 5 : 10;
    return Math.max(1, nice * magnitude);
  }

  function renderBars(month, daily, week) {
    const start = (week - 1) * 7;
    const days = daily.slice(start, start + 7);
    const step = axisStep(Math.max(0, ...days));
    const axisMax = step * 3;

    visitorAxis.forEach((span, index) => {
      span.textContent = numberFormat.format(step * (visitorAxis.length - 1 - index));
    });
    visitorBars.replaceChildren(
      ...days.map((value, index) => {
        const item = document.createElement("li");
        const bar = document.createElement("span");
        const label = document.createElement("span");
        bar.className = "bar-chart__bar";
        bar.style.setProperty("--ratio", Math.min(value, axisMax) / axisMax);
        bar.title = `${numberFormat.format(value)}명`;
        label.textContent = `${pad(month)}. ${pad(start + index + 1)}`;
        item.setAttribute("aria-label", `${month}월 ${start + index + 1}일 방문자 ${value}명`);
        item.append(bar, label);
        return item;
      }),
    );
  }

  // 월 바꿀 때 기본 주차: 오늘이 속한 달이면 오늘이 있는 주차, 아니면 1주차
  function defaultWeek(year, month) {
    const now = today ? today.split("-").map(Number) : [];
    return now[0] === year && now[1] === month ? Math.ceil(now[2] / 7) : 1;
  }

  let monthStats = null;
  let statsRequest = 0;

  function renderWeek() {
    const [, month] = monthInput.value.split("-").map(Number);
    renderBars(month, monthStats.daily, Number(weekSelect.value) || 1);
  }

  async function renderStats() {
    const [year, month] = monthInput.value.split("-").map(Number);
    const requestId = ++statsRequest;
    monthLabel.textContent = `${year}. ${pad(month)}`;
    monthTrigger.setAttribute("aria-label", `조회 월 선택, ${year}년 ${month}월`);
    document.querySelectorAll(".js-month-name").forEach((node) => {
      node.textContent = `${month}월`;
    });

    let stats;
    try {
      stats = await loadMonthStats(year, month);
    } catch (error) {
      if (requestId !== statsRequest) return;
      monthStats = null;
      const item = document.createElement("li");
      item.textContent = error.message;
      visitorBars.replaceChildren(item);
      return;
    }
    if (requestId !== statsRequest) return;

    monthStats = stats;
    const weekCount = Math.ceil(stats.daily.length / 7);
    document.querySelector("#chatbot-rate").textContent = `${stats.chatbotRate}%`;
    document.querySelector("#visitor-total").textContent = numberFormat.format(stats.visitorTotal);

    weekSelect.replaceChildren(
      ...Array.from({ length: weekCount }, (_, index) => new Option(`${month}월 ${index + 1}주차`, index + 1)),
    );
    weekSelect.value = Math.min(defaultWeek(year, month), weekCount);
    renderWeek();

    const { members } = stats;
    const donut = document.querySelector("#member-donut");
    donut.style.setProperty("--withdrawn", members.rate);
    donut.setAttribute("aria-label", `${DONUT_LABEL} ${members.rate}%`);
    document.querySelector("#member-withdrawn").textContent = `${members.rate}%`;
    document.querySelector("#member-total").textContent = `${numberFormat.format(members.total)} 명`;
    document.querySelector("#member-month").textContent = `${numberFormat.format(members.month)} 명`;
    document.querySelector("#member-today").textContent = `${numberFormat.format(members.today)} 명`;
  }

  function setMonth(year, month) {
    const next = new Date(year, month - 1, 1);
    monthInput.value = `${next.getFullYear()}-${pad(next.getMonth() + 1)}`;
    renderStats();
  }

  function shiftMonth(offset) {
    const [year, month] = monthInput.value.split("-").map(Number);
    setMonth(year, month + offset);
  }

  // 월 선택 패널: 연도 이동 + 12개월 격자 + 이번 달. 브라우저 기본 month 피커 대신 사용
  const monthTrigger = document.querySelector("#month-trigger");
  const monthPanel = document.querySelector("#month-panel");
  const monthPanelYear = document.querySelector("#month-panel-year");
  const monthPanelGrid = document.querySelector("#month-panel-grid");
  let panelYear = 0;

  function currentYearMonth() {
    const [year, month] = (today || "").split("-").map(Number);
    if (year && month) return [year, month];
    const now = new Date();
    return [now.getFullYear(), now.getMonth() + 1];
  }

  function renderMonthPanel(focusMonth) {
    const [selectedYear, selectedMonth] = monthInput.value.split("-").map(Number);
    const [thisYear, thisMonth] = currentYearMonth();
    monthPanelYear.textContent = `${panelYear}년`;
    monthPanelGrid.replaceChildren(
      ...Array.from({ length: 12 }, (_, index) => {
        const month = index + 1;
        const button = document.createElement("button");
        const selected = panelYear === selectedYear && month === selectedMonth;
        button.type = "button";
        button.className = "month-panel__month";
        button.textContent = `${month}월`;
        button.dataset.month = String(month);
        button.tabIndex = month === focusMonth ? 0 : -1;
        button.setAttribute("aria-label", `${panelYear}년 ${month}월`);
        if (selected) button.setAttribute("aria-current", "date");
        if (panelYear === thisYear && month === thisMonth) button.classList.add("is-this-month");
        return button;
      }),
    );
  }

  function focusPanelMonth(month) {
    monthPanelGrid.querySelector(`[data-month="${month}"]`)?.focus();
  }

  function openMonthPanel() {
    const [year, month] = monthInput.value.split("-").map(Number);
    panelYear = year;
    renderMonthPanel(month);
    monthPanel.hidden = false;
    monthTrigger.setAttribute("aria-expanded", "true");
    focusPanelMonth(month);
  }

  function closeMonthPanel(restoreFocus = true) {
    if (monthPanel.hidden) return;
    monthPanel.hidden = true;
    monthTrigger.setAttribute("aria-expanded", "false");
    if (restoreFocus) monthTrigger.focus();
  }

  monthTrigger.addEventListener("click", () => {
    if (monthPanel.hidden) openMonthPanel();
    else closeMonthPanel();
  });

  monthPanel.addEventListener("click", (event) => {
    const step = event.target.closest("[data-year-step]");
    if (step) {
      panelYear += Number(step.dataset.yearStep);
      const focused = Number(document.activeElement?.dataset?.month) || 1;
      renderMonthPanel(focused);
      return;
    }
    const monthButton = event.target.closest(".month-panel__month");
    if (monthButton) {
      setMonth(panelYear, Number(monthButton.dataset.month));
      closeMonthPanel();
      return;
    }
    if (event.target.closest("#month-panel-today")) {
      setMonth(...currentYearMonth());
      closeMonthPanel();
    }
  });

  monthPanel.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      event.preventDefault();
      closeMonthPanel();
      return;
    }
    const month = Number(event.target.dataset?.month);
    const moves = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -4, ArrowDown: 4 };
    if (!month || !(event.key in moves)) return;
    event.preventDefault();
    let next = month + moves[event.key];
    if (next < 1 || next > 12) {
      panelYear += next < 1 ? -1 : 1;
      next = ((next - 1 + 12) % 12) + 1;
    }
    renderMonthPanel(next);
    focusPanelMonth(next);
  });

  document.addEventListener("pointerdown", (event) => {
    if (!event.target.closest(".month-nav__picker")) closeMonthPanel(false);
  });
  monthPanel.addEventListener("focusout", (event) => {
    if (event.relatedTarget && !event.relatedTarget.closest(".month-nav__picker")) closeMonthPanel(false);
  });

  function createBadge(text, tone) {
    const badge = document.createElement("span");
    badge.className = tone ? `badge badge--${tone}` : "badge";
    badge.textContent = text;
    badge.title = text;
    return badge;
  }

  function createRow(cells) {
    const row = document.createElement("tr");
    cells.forEach((cell) => {
      const td = document.createElement("td");
      if (cell instanceof Node) {
        td.append(cell);
      } else {
        td.textContent = cell;
        // 칸이 좁아 말줄임될 수 있으므로 전체 값을 title 로
        td.title = cell;
      }
      row.append(td);
    });
    return row;
  }

  function createPagedTable({ rows, pagination, columns, toCells, emptyText, linkOf }) {
    let items = [];
    let currentPage = 1;
    let message = emptyText;

    function pageButton(label, page, { disabled = false, current = false } = {}) {
      const button = document.createElement("button");
      button.type = "button";
      button.textContent = label;
      button.disabled = disabled;
      if (current) button.setAttribute("aria-current", "page");
      button.addEventListener("click", () => {
        currentPage = page;
        render();
      });
      return button;
    }

    function render() {
      const totalPages = Math.max(1, Math.ceil(items.length / PAGE_SIZE));
      currentPage = Math.min(Math.max(1, currentPage), totalPages);
      const offset = (currentPage - 1) * PAGE_SIZE;
      const pageItems = items.slice(offset, offset + PAGE_SIZE);

      if (pageItems.length) {
        rows.replaceChildren(
          ...pageItems.map((item, index) => {
            const row = createRow(toCells(item, offset + index + 1));
            row.children[columns.leftIndex].className = "is-left";
            const href = linkOf?.(item);
            if (href) {
              row.className = "is-link";
              row.dataset.href = href;
              row.tabIndex = 0;
            }
            return row;
          }),
        );
      } else {
        const row = createRow([message]);
        row.className = "empty-row";
        row.firstChild.colSpan = columns.count;
        rows.replaceChildren(row);
      }

      const groupStart = Math.floor((currentPage - 1) / PAGE_GROUP_SIZE) * PAGE_GROUP_SIZE + 1;
      const groupEnd = Math.min(groupStart + PAGE_GROUP_SIZE - 1, totalPages);
      const buttons = [pageButton("이전", currentPage - 1, { disabled: currentPage === 1 })];
      for (let page = groupStart; page <= groupEnd; page += 1) {
        buttons.push(pageButton(String(page), page, { current: page === currentPage }));
      }
      buttons.push(pageButton("다음", currentPage + 1, { disabled: currentPage === totalPages }));
      pagination.replaceChildren(...buttons);
    }

    // 행 클릭·Enter 로 상세 이동
    const openRow = (event) => {
      const row = event.target.closest("tr[data-href]");
      if (row) location.href = row.dataset.href;
    };
    rows.addEventListener("click", openRow);
    rows.addEventListener("keydown", (event) => {
      if (event.key === "Enter") openRow(event);
    });

    return {
      setItems(nextItems, nextMessage = emptyText) {
        items = nextItems;
        message = nextMessage;
        currentPage = 1;
        render();
      },
    };
  }

  function buildMockList(seedItems, total, makeItem) {
    const random = seededRandom(total * 7919);
    const list = [...seedItems];
    while (list.length < total) list.push(makeItem(random, list.length));
    return list;
  }

  function mockLists() {
    const NAMES = ["홍길동", "윤수로", "윤진원", "이건규", "이혜지", "김윤영"];
    const pick = (random, list) => list[Math.floor(random() * list.length)];

    const inquiries = buildMockList(
      [
        { author: "홍길동", company: "길동컴퍼니", content: "딸~깍 ㅋㅋㅋㅋㅋㅋㅋㅋㅋㅋㅋㅋㅋㅋ", date: "2026. 09. 11", status: "접수 완료" },
        { author: "윤수로", company: "수로TV", content: "못받은돈 떼인돈 찾아드립니다", date: "2026. 09. 10", status: "검토 중" },
        { author: "윤진원", company: "피자10판", content: "제가 피자 10판 쏘겠습니다~", date: "2026. 08. 07", status: "처리 완료" },
        { author: "이건규", company: "규니재이", content: "안녕하십니까", date: "2026. 08. 06", status: "답변 대기" },
        { author: "이혜지", company: "나담배핀다", content: "아 ㅆㅂ 내꺼 왜 반려됐는데", date: "2026. 08. 05", status: "반려" },
      ],
      40,
      (random, index) => ({
        author: pick(random, NAMES),
        company: `${pick(random, ["퓨즈", "스튜디오", "랩스", "컴퍼니"])} ${index + 1}`,
        content: pick(random, ["웹사이트 리뉴얼 견적 문의드립니다", "브랜드 디자인 의뢰", "MVP 개발 일정 상담", "유지보수 비용 문의"]),
        date: `2026. ${pad(8 - Math.floor(index / 10))}. ${pad(28 - (index % 25))}`,
        status: pick(random, Object.keys(STATUS_BADGES)),
      }),
    );

    const posts = buildMockList(
      [
        { category: "공지사항", title: "2026년 하반기 운영 일정 안내", author: "홍길동", date: "09-05", views: 1842, visible: true },
        { category: "프로젝트", title: "신규 웹 프로젝트 진행 일정 공유", author: "김윤영", date: "09-05", views: 1842, visible: true },
        { category: "개발", title: "UI/UX 디자인 가이드 개편 안내", author: "김윤영", date: "09-05", views: 1842, visible: true },
        { category: "운영", title: "프론트엔드 개발 환경 업데이트 안내", author: "김윤영", date: "09-05", views: 1842, visible: true },
        { category: "디자인", title: "메인 페이지 디자인 시안 안내", author: "김윤영", date: "09-05", views: 1842, visible: true },
      ],
      40,
      (random, index) => {
        const category = pick(random, Object.keys(CATEGORY_BADGES));
        return {
          category,
          title: `${category} 관련 업데이트 공유 #${index + 1}`,
          author: pick(random, NAMES),
          date: `${pad(9 - Math.floor(index / 12))}-${pad(28 - (index % 25))}`,
          views: Math.round(random() * 3000),
          visible: random() > 0.2,
        };
      },
    ).map((post) => ({ ...post, board: post.category }));

    const boards = Object.fromEntries(Object.keys(CATEGORY_BADGES).map((label) => [label, label]));
    return { inquiries, posts, boards };
  }

  const inquiryTable = createPagedTable({
    rows: document.querySelector("#inquiry-rows"),
    pagination: document.querySelector("#inquiry-pagination"),
    columns: { count: 6, leftIndex: 3 },
    emptyText: dashboardApi ? "접수된 문의가 없습니다." : "등록된 내용이 없습니다.",
    linkOf: (item) => (inquiryUrl && item.id ? `${inquiryUrl}?id=${encodeURIComponent(item.id)}` : ""),
    toCells: (item, number) => [
      number,
      item.author,
      item.company,
      item.content,
      item.date,
      createBadge(item.status, STATUS_BADGES[item.status]),
    ],
  });

  const postTable = createPagedTable({
    rows: document.querySelector("#post-rows"),
    pagination: document.querySelector("#post-pagination"),
    columns: { count: 7, leftIndex: 2 },
    emptyText: dashboardApi ? "등록된 게시글이 없습니다." : "등록된 내용이 없습니다.",
    linkOf: (item) => (postEditUrl && item.id ? `${postEditUrl}?id=${encodeURIComponent(item.id)}` : ""),
    toCells: (item, number) => [
      number,
      createBadge(item.category, BOARD_BADGES[item.board]),
      item.title,
      item.author,
      item.date,
      numberFormat.format(item.views),
      item.visible ? "노출" : "숨김",
    ],
  });

  let posts = [];

  function selectTab(tab) {
    postTabs.querySelectorAll("button").forEach((button) => {
      button.setAttribute("aria-selected", String(button.dataset.tab === tab));
    });
    postTable.setItems(tab === "ALL" ? posts : posts.filter((post) => post.board === tab));
  }

  function renderTabs(boards) {
    const tabs = [["ALL", "ALL"], ...Object.entries(boards)];
    postTabs.replaceChildren(
      ...tabs.map(([key, label]) => {
        const button = document.createElement("button");
        button.type = "button";
        button.role = "tab";
        button.dataset.tab = key;
        button.textContent = label;
        button.title = label;
        button.addEventListener("click", () => selectTab(key));
        return button;
      }),
    );
  }

  async function loadLists() {
    if (!dashboardApi) {
      const lists = mockLists();
      posts = lists.posts;
      renderTabs(lists.boards);
      inquiryTable.setItems(lists.inquiries);
      selectTab("ALL");
      return;
    }
    try {
      const lists = await requestApi({ action: "lists" });
      posts = lists.posts;
      renderTabs(lists.boards);
      inquiryTable.setItems(lists.inquiries);
      selectTab("ALL");
    } catch (error) {
      inquiryTable.setItems([], error.message);
      postTable.setItems([], error.message);
    }
  }

  document.querySelector("#month-prev").addEventListener("click", () => shiftMonth(-1));
  document.querySelector("#month-next").addEventListener("click", () => shiftMonth(1));
  weekSelect.addEventListener("change", () => {
    if (monthStats) renderWeek();
  });

  renderStats();
  loadLists();
})();
