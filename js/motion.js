// 사용자 화면 등장·패럴랙스 연출 (GSAP + ScrollTrigger). script.js 의 섹션 스크롤 구조는 건드리지 않고 글자·요소 등장만 얹는다
(() => {
  if (!window.gsap || !window.ScrollTrigger) return;
  gsap.registerPlugin(ScrollTrigger);

  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  // 포트폴리오 상세 첫 화면 화살표: 스크롤을 시작하면 반복 애니메이션을 멈추고 숨김
  const fullImage = document.querySelector(".portfolio-detail-sec-full-img");
  if (fullImage) {
    const syncArrow = () => fullImage.classList.toggle("is-scrolled", window.scrollY > 10);
    syncArrow();
    window.addEventListener("scroll", syncArrow, { passive: true });
  }

  if (reducedMotion) return;

  // 글자 등장 (아래에서 올라오며 블러가 걷힘). 글자 수가 많아도 전체 지연은 maxStagger 안에서 끝남
  const CHAR_FROM = { opacity: 0, yPercent: 60, filter: "blur(8px)" };
  const CHAR_TO = { opacity: 1, yPercent: 0, filter: "blur(0px)", duration: 0.8, ease: "expo.out" };
  const RISE_FROM = { opacity: 0, y: 24, filter: "blur(6px)" };
  const RISE_TO = { opacity: 1, y: 0, filter: "blur(0px)", duration: 0.9, ease: "power3.out" };
  const spread = (count, maxStagger) => (count > 1 ? Math.min(0.03, maxStagger / (count - 1)) : 0);

  // 텍스트 노드를 단어(.fx-word, 줄바꿈 단위) > 글자(.fx-char) span 으로 나눔. <br> 과 하위 요소 구조는 유지
  const splitChars = (element) => {
    if (element.dataset.fxSplit !== undefined) return [...element.querySelectorAll(".fx-char")];
    element.dataset.fxSplit = "";
    const walk = (node) => {
      [...node.childNodes].forEach((child) => {
        if (child.nodeType === Node.ELEMENT_NODE) {
          if (child.tagName !== "BR") walk(child);
          return;
        }
        if (child.nodeType !== Node.TEXT_NODE || child.textContent.trim() === "") return;
        const fragment = document.createDocumentFragment();
        child.textContent.split(/(\s+)/).forEach((part) => {
          if (part === "") return;
          if (/^\s+$/.test(part)) {
            fragment.append(" ");
            return;
          }
          const word = document.createElement("span");
          word.className = "fx-word";
          for (const character of part) {
            const span = document.createElement("span");
            span.className = "fx-char";
            span.textContent = character;
            word.append(span);
          }
          fragment.append(word);
        });
        child.replaceWith(fragment);
      });
    };
    walk(element);
    const letters = [...element.querySelectorAll(".fx-char")];
    if (getComputedStyle(element).webkitBackgroundClip === "text") keepTextGradient(element, letters);
    return letters;
  };

  // background-clip: text 그라디언트 제목은 글자에 transform·filter 가 걸리면 부모 그라디언트가 깨져 보임.
  // 글자마다 부모와 같은 그라디언트를 부모 크기·위치에 맞춰 칠해 이어져 보이게 함.
  // 이탤릭·세리프 글자는 획이 글자 상자 밖으로 나가 그 부분이 칠해지지 않으므로 사방에 여백(padding)을 주고 같은 만큼 margin 으로 되돌림
  const GLYPH_BLEED = "0.3em";
  const keepTextGradient = (element, letters) => {
    const backgroundImage = getComputedStyle(element).backgroundImage;
    // offset* 는 transform 을 무시하므로 글자가 움직이는 중에 다시 재도 위치가 맞음 (글자·제목의 offsetParent 가 같음)
    const paint = () => {
      letters.forEach((letter) => {
        Object.assign(letter.style, {
          backgroundImage,
          backgroundSize: `${element.offsetWidth}px ${element.offsetHeight}px`,
          backgroundPosition: `${element.offsetLeft - letter.offsetLeft}px ${element.offsetTop - letter.offsetTop}px`,
          webkitBackgroundClip: "text",
          backgroundClip: "text",
        });
      });
    };
    element.style.backgroundImage = "none";
    letters.forEach((letter) => {
      letter.style.webkitTextFillColor = "transparent";
      letter.style.padding = GLYPH_BLEED;
      letter.style.margin = `calc(-1 * ${GLYPH_BLEED})`;
    });
    paint();
    window.addEventListener("resize", paint);
    document.fonts?.ready.then(paint);
  };

  const toList = (scope, targets) =>
    (Array.isArray(targets) ? targets : [targets]).flatMap((target) =>
      typeof target === "string" ? [...scope.querySelectorAll(target)] : target ? [target] : [],
    );

  // chars: 글자 단위로 나눌 요소, rise: 통째로 올라올 요소. 멈춘 타임라인을 돌려주고 처음 상태(숨김)로 둔다
  const buildReveal = (scope, { chars = [], rise = [], maxStagger = 0.4 }) => {
    const timeline = gsap.timeline({ paused: true });
    toList(scope, chars).forEach((element, index) => {
      const letters = splitChars(element);
      if (!letters.length) return;
      timeline.fromTo(
        letters,
        CHAR_FROM,
        { ...CHAR_TO, stagger: spread(letters.length, maxStagger) },
        index === 0 ? 0 : "<0.12",
      );
    });
    const risers = toList(scope, rise);
    if (risers.length) {
      timeline.fromTo(risers, RISE_FROM, { ...RISE_TO, stagger: 0.08 }, timeline.duration() > 0 ? "<0.1" : 0);
    }
    return timeline;
  };

  // 화면에 들어오면 한 번 재생
  const revealOnScroll = (trigger, options, start = "top 75%") => {
    if (!trigger) return;
    const timeline = buildReveal(trigger, options);
    ScrollTrigger.create({ trigger, start, once: true, onEnter: () => timeline.play() });
  };

  // 슬라이드형 섹션: 활성 항목이 바뀔 때마다 그 항목만 처음부터 재생, 나머지는 숨김 상태로 되돌림
  const revealActive = (items, options, isActive, section, watch) => {
    if (!section || !items.length) return;
    const timelines = items.map((item) => buildReveal(item, options));
    const playing = new Set();
    let sectionVisible = false;
    const sync = () => {
      items.forEach((item, index) => {
        const on = sectionVisible && isActive(item, index);
        if (on && !playing.has(index)) {
          playing.add(index);
          timelines[index].restart();
        } else if (!on && playing.has(index)) {
          playing.delete(index);
          timelines[index].pause(0);
        }
      });
    };
    new IntersectionObserver(
      ([entry]) => {
        sectionVisible = entry.isIntersecting;
        sync();
      },
      { rootMargin: "0px 0px -40% 0px" },
    ).observe(section);
    new MutationObserver(sync).observe(watch.target, watch.options);
  };

  // 1섹션(hero) → 2섹션(포트폴리오): hero 내용을 스크롤한 만큼 아래로 옮겨 화면에 고정된 것처럼 두고(background-attachment: fixed 느낌),
  // hero 상자(overflow: hidden)만 위로 올라가며 다음 섹션이 아래에서 덮음. 덮이는 동안 hero 는 블러되며 흐려짐
  const hero = document.querySelector(".hero");
  const story = document.querySelector(".gyu-story");
  if (hero && story) {
    gsap.to(hero.children, {
      y: () => hero.offsetHeight,
      filter: "blur(12px)",
      opacity: 0.3,
      ease: "none",
      scrollTrigger: {
        trigger: hero,
        start: "top top",
        end: "bottom top",
        scrub: true,
        invalidateOnRefresh: true,
      },
    });

    const firstImage = story.querySelector(".gyu-project .gyu-project__image");
    if (firstImage) {
      gsap.fromTo(
        firstImage,
        { scale: 1.18, yPercent: -6 },
        {
          scale: 1,
          yPercent: 0,
          ease: "none",
          scrollTrigger: { trigger: story, start: "top bottom", end: "top top", scrub: true },
        },
      );
    }
  }

  // 메인 포트폴리오 슬라이드: 현재 슬라이드의 번호·제목·설명·버튼 등장
  if (story) {
    const projects = [...story.querySelectorAll(".gyu-project")];
    revealActive(
      projects,
      {
        chars: [".gyu-project__eyebrow", ".gyu-project__title", ".gyu-project__description"],
        rise: [".gyu-project__index", ".gyu-project__button"],
        maxStagger: 0.35,
      },
      (project) => project.classList.contains("is-gyu-current"),
      story,
      { target: story, options: { subtree: true, attributeFilter: ["class"] } },
    );
  }

  // From Idea to System.
  revealOnScroll(document.querySelector(".divider__text"), {
    chars: [".divider__title", ".divider__desc"],
    maxStagger: 0.5,
  }, "top 80%");

  // From Idea to System. 핀 고정 → 스크롤하면 제목 글자만 커지면서 서로 멀어지고 블러·흐려짐(startangelinus 첫 화면 로고 연출 방식).
  // 구 이미지·작은 문구·배경은 확대하지 않고 흐려지기만 함.
  // 서비스 섹션은 이 섹션 아래(z-index)에 미리 자리 잡아 두고, 마지막에 이 섹션이 걷히면서 드러남
  const PIN_LENGTH = 1.2; // 핀 길이 (화면 높이 배수)
  const REVEAL_AT = 0.8; // 이 진행률에 서비스 섹션이 화면 맨 위에 닿고, 여기서부터 이 섹션이 걷힘
  const divider = document.querySelector(".divider");
  const dividerTitle = divider?.querySelector(".divider__title");
  if (divider && dividerTitle) {
    const nextSection = document.querySelector(".gyu-mvp");
    const nextBackground = getComputedStyle(nextSection ?? divider).backgroundColor;
    const titleChars = splitChars(dividerTitle);
    const middle = (titleChars.length - 1) / 2;
    document.documentElement.classList.add("fx-divider-pin");
    // 서비스 섹션을 위로 당겨 핀 진행률 REVEAL_AT 에서 화면 맨 위에 닿게 함.
    // 남은 핀 구간((1 - REVEAL_AT) × 핀 길이)은 서비스 섹션 첫 카드 대기 구간(화면 0.3배) 안에 들어가 카드가 움직이지 않음
    const placeNextSection = () => {
      if (!nextSection) return;
      nextSection.style.marginTop =
        `${-(divider.offsetHeight + window.innerHeight * PIN_LENGTH * (1 - REVEAL_AT))}px`;
    };
    placeNextSection();
    ScrollTrigger.addEventListener("refreshInit", placeNextSection);
    gsap
      .timeline({
        defaults: { ease: "none" },
        scrollTrigger: {
          trigger: divider,
          start: "top top",
          end: () => `+=${window.innerHeight * PIN_LENGTH}`,
          pin: true,
          scrub: true,
          // 아래 섹션(서비스·why·contact)의 핀보다 먼저 계산해야 그 위치에 이 핀 길이가 반영됨
          refreshPriority: 1,
          invalidateOnRefresh: true,
          onUpdate: ({ progress }) => {
            // 배경이 어두워진 뒤에는 헤더를 밝은 글자로
            divider.dataset.headerTheme = progress > 0.5 ? "light" : "dark";
            // 서비스 섹션이 가려져 있는 동안은 카드 문구 등장을 미뤘다가 걷히기 시작할 때 재생
            if (nextSection) nextSection.dataset.fxCovered = String(progress < REVEAL_AT);
          },
        },
      })
      .to(dividerTitle, { scale: 3.2, ease: "power1.in", duration: 0.75 }, 0)
      // 가운데 글자를 기준으로 양옆 글자가 멀어짐 (제목 글자 크기에 비례)
      .to(titleChars, {
        x: (index) => (index - middle) * dividerTitle.offsetHeight * 0.18,
        ease: "power1.in",
        duration: 0.75,
      }, 0)
      .to(dividerTitle, { filter: "blur(16px)", ease: "power2.in", duration: 0.45 }, 0.15)
      .to(dividerTitle, { opacity: 0, ease: "power1.in", duration: 0.3 }, 0.45)
      .to([divider.querySelector(".divider__desc"), divider.querySelector(".divider__marquee")], { opacity: 0, duration: 0.25 }, 0)
      // .divider__sphere 는 CSS transition(진입 연출)이 걸려 있어 안쪽 img 를 흐리게 함
      .to(divider.querySelector(".divider__sphere img"), { opacity: 0, duration: 0.4 }, 0.15)
      .to(divider, { backgroundColor: nextBackground, duration: 0.45 }, 0.3)
      // 아래에 미리 자리 잡은 서비스 섹션이 보이도록 이 섹션을 걷어냄
      .to(divider, { opacity: 0, duration: 1 - REVEAL_AT }, REVEAL_AT);
  }

  // 서비스 카드: 앞에 놓인 카드의 문구 등장
  const mvp = document.querySelector(".gyu-mvp");
  const deck = mvp?.querySelector(".gyu-mvp__deck");
  if (deck) {
    const cards = [...deck.querySelectorAll(".gyu-service-card")];
    revealActive(
      cards,
      { chars: ["h2", "p"], rise: ["h3"], maxStagger: 0.45 },
      (card, index) => mvp.dataset.fxCovered !== "true" && deck.dataset.activeIndex === String(index),
      mvp,
      { target: mvp, options: { subtree: true, attributeFilter: ["data-active-index", "data-fx-covered"] } },
    );
  }

  // CONTACT 왼쪽 문구
  revealOnScroll(document.querySelector(".contact-inner .left-box"), {
    chars: [".title", ".text-1", ".text-2"],
    maxStagger: 0.45,
  });

  // 포트폴리오 상세 Overview
  revealOnScroll(document.querySelector(".portfolio-detail-Overview-grid"), {
    chars: [".title", ".body-title", ".body-box p"],
    rise: [".meta-item", ".btn-box"],
    maxStagger: 0.6,
  }, "top 80%");

  ScrollTrigger.refresh();
})();
