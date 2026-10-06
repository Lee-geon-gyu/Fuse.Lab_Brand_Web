(() => {
  const PAGE_SIZE = 5;
  const PAGE_GROUP_SIZE = 10;
  const AXIS_MAX = 600;

  const STATUS_BADGES = {
    "접수 완료": "green",
    "검토 중": "yellow",
    "처리 완료": "blue",
    "답변 대기": "",
    반려: "pink",
  };
  const CATEGORY_BADGES = {
    공지사항: "green",
    프로젝트: "purple",
    디자인: "pink",
    개발: "blue",
    운영: "",
  };
  const POST_TABS = ["ALL", ...Object.keys(CATEGORY_BADGES)];

  const monthInput = document.querySelector("#month-input");
  const monthLabel = document.querySelector("#month-label");
  const weekSelect = document.querySelector("#week-select");
  const visitorBars = document.querySelector("#visitor-bars");
  const postTabs = document.querySelector("#post-tabs");

  const numberFormat = new Intl.NumberFormat("ko-KR");
  const pad = (value) => String(value).padStart(2, "0");

  // 백엔드 연동 전 목업 데이터: 월 키 기반 시드로 매번 같은 값 생성
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
      members: { total: 5877, month: 4485, today: 85, withdrawn: 6.9 },
    },
  };

  function getMonthStats(year, month) {
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
        withdrawn: Math.round(random() * 150) / 10,
      },
    };
  }

  function renderBars(year, month, daily, week) {
    const start = (week - 1) * 7;
    const days = daily.slice(start, start + 7);

    visitorBars.replaceChildren(
      ...days.map((value, index) => {
        const item = document.createElement("li");
        const bar = document.createElement("span");
        const label = document.createElement("span");
        bar.className = "bar-chart__bar";
        bar.style.setProperty("--ratio", Math.min(value, AXIS_MAX) / AXIS_MAX);
        bar.title = `${numberFormat.format(value)}명`;
        label.textContent = `${pad(month)}. ${pad(start + index + 1)}`;
        item.setAttribute("aria-label", `${month}월 ${start + index + 1}일 방문자 ${value}명`);
        item.append(bar, label);
        return item;
      }),
    );
  }

  function renderStats() {
    const [year, month] = monthInput.value.split("-").map(Number);
    const stats = getMonthStats(year, month);
    const weekCount = Math.ceil(stats.daily.length / 7);
    const selectedWeek = Math.min(Number(weekSelect.value) || 1, weekCount);

    monthLabel.textContent = `${year}. ${pad(month)}`;
    document.querySelector("#chatbot-rate").textContent = `${stats.chatbotRate}%`;
    document.querySelector("#visitor-total").textContent = numberFormat.format(stats.visitorTotal);

    weekSelect.replaceChildren(
      ...Array.from({ length: weekCount }, (_, index) => new Option(`${month}월 ${index + 1}주차`, index + 1)),
    );
    weekSelect.value = selectedWeek;
    renderBars(year, month, stats.daily, selectedWeek);

    const { members } = stats;
    const donut = document.querySelector("#member-donut");
    donut.style.setProperty("--withdrawn", members.withdrawn);
    donut.setAttribute("aria-label", `탈퇴 회원 비율 ${members.withdrawn}%`);
    document.querySelector("#member-withdrawn").textContent = `${members.withdrawn}%`;
    document.querySelector("#member-total").textContent = `${numberFormat.format(members.total)} 명`;
    document.querySelector("#member-month").textContent = `${members.month} 명`;
    document.querySelector("#member-today").textContent = `${members.today} 명`;
  }

  function shiftMonth(offset) {
    const [year, month] = monthInput.value.split("-").map(Number);
    const next = new Date(year, month - 1 + offset, 1);
    monthInput.value = `${next.getFullYear()}-${pad(next.getMonth() + 1)}`;
    renderStats();
  }

  function createBadge(text, tone) {
    const badge = document.createElement("span");
    badge.className = tone ? `badge badge--${tone}` : "badge";
    badge.textContent = text;
    return badge;
  }

  function createRow(cells) {
    const row = document.createElement("tr");
    cells.forEach((cell) => {
      const td = document.createElement("td");
      if (cell instanceof Node) td.append(cell);
      else td.textContent = cell;
      row.append(td);
    });
    return row;
  }

  function createPagedTable({ rows, pagination, columns, toCells }) {
    let items = [];
    let currentPage = 1;

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
            return row;
          }),
        );
      } else {
        const row = createRow(["등록된 내용이 없습니다."]);
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

    return {
      setItems(nextItems) {
        items = nextItems;
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
  );

  const inquiryTable = createPagedTable({
    rows: document.querySelector("#inquiry-rows"),
    pagination: document.querySelector("#inquiry-pagination"),
    columns: { count: 6, leftIndex: 3 },
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
    toCells: (item, number) => [
      number,
      createBadge(item.category, CATEGORY_BADGES[item.category]),
      item.title,
      item.author,
      item.date,
      numberFormat.format(item.views),
      item.visible ? "노출" : "숨김",
    ],
  });

  function selectTab(tab) {
    postTabs.querySelectorAll("button").forEach((button) => {
      button.setAttribute("aria-selected", String(button.dataset.tab === tab));
    });
    postTable.setItems(tab === "ALL" ? posts : posts.filter((post) => post.category === tab));
  }

  postTabs.replaceChildren(
    ...POST_TABS.map((tab) => {
      const button = document.createElement("button");
      button.type = "button";
      button.role = "tab";
      button.dataset.tab = tab;
      button.textContent = tab;
      button.addEventListener("click", () => selectTab(tab));
      return button;
    }),
  );

  document.querySelector("#month-prev").addEventListener("click", () => shiftMonth(-1));
  document.querySelector("#month-next").addEventListener("click", () => shiftMonth(1));
  monthInput.addEventListener("click", () => {
    try {
      monthInput.showPicker();
    } catch {}
  });
  monthInput.addEventListener("change", () => {
    if (monthInput.value) renderStats();
  });
  weekSelect.addEventListener("change", renderStats);

  renderStats();
  inquiryTable.setItems(inquiries);
  selectTab("ALL");
})();
