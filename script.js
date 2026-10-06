// ==================== Gyu Lane Start ====================
(() => {
  const story = document.querySelector(".gyu-story");
  const stage = story?.querySelector(".gyu-stage");
  const track = stage?.querySelector(".gyu-track");
  const projects = Array.from(track?.querySelectorAll(".gyu-project") ?? []);
  const themeButton = document.querySelector(".gyu-theme");

  if (!story || !stage || !track || projects.length === 0) return;

  if ("scrollRestoration" in window.history) {
    window.history.scrollRestoration = "manual";
  }

  const navigationEntry = window.performance
    .getEntriesByType?.("navigation")
    .at?.(0);
  const shouldResetOnLoad = navigationEntry?.type === "reload";
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
  const transitionCount = Math.max(projects.length - 1, 0);
  const holdScrollRatio = 0.65;
  const transitionScrollRatio = 1.85;

  let segmentLength = 1;
  let holdLength = 1;
  let transitionLength = 1;
  let finalHoldLength = 1;
  let targetTimeline = 0;
  let displayTimeline = 0;
  let animationFrame = 0;
  let previousTime = performance.now();

  const clamp = (value, min = 0, max = 1) =>
    Math.min(Math.max(value, min), max);
  const mix = (from, to, progress) => from + (to - from) * progress;
  const ease = (value) =>
    value * value * value * (value * (value * 6 - 15) + 10);
  const padNumber = (number) => String(number).padStart(2, "0");

  const prepareProjects = () => {
    const total = padNumber(projects.length);

    projects.forEach((project, index) => {
      const number = padNumber(index + 1);
      const title = project.querySelector(".gyu-project__title");
      const counter = project.querySelector(".gyu-project__index");
      const image = project.querySelector(".gyu-project__image");

      project.id = `gyu-project-${number}`;
      project.dataset.gyuProjectIndex = String(index);

      if (title) {
        title.id = `gyu-title-${number}`;
        project.setAttribute("aria-labelledby", title.id);
      }

      if (counter) {
        const current = counter.querySelector("strong");
        const counterParts = counter.querySelectorAll("span");
        if (current) current.textContent = number;
        if (counterParts.length > 0) {
          counterParts[counterParts.length - 1].textContent = total;
        }
        counter.setAttribute("aria-label", `${index + 1} / ${projects.length}`);
      }

      if (image) {
        image.loading = "eager";
        image.decoding = index === 0 ? "sync" : "async";
        image.fetchPriority = index < 2 ? "high" : "auto";
      }
    });
  };

  const syncStoryHeight = () => {
    holdLength = Math.max(window.innerHeight * holdScrollRatio, 1);
    transitionLength = Math.max(window.innerHeight * transitionScrollRatio, 1);
    segmentLength = holdLength + transitionLength;
    finalHoldLength = holdLength;
    story.style.height = `${
      window.innerHeight + transitionCount * segmentLength + finalHoldLength
    }px`;
  };

  const getStoryTop = () => story.getBoundingClientRect().top + window.scrollY;

  const getTimelinePosition = () => {
    if (transitionCount === 0) return 0;
    const storyOffset = Math.max(window.scrollY - getStoryTop(), 0);
    const transitionStoryLength = transitionCount * segmentLength;

    if (storyOffset >= transitionStoryLength) return transitionCount;

    const segmentIndex = Math.floor(storyOffset / segmentLength);
    const segmentOffset = storyOffset - segmentIndex * segmentLength;
    const transitionProgress = clamp(
      (segmentOffset - holdLength) / transitionLength,
    );

    return clamp(segmentIndex + transitionProgress, 0, transitionCount);
  };

  const render = (timeline) => {
    const safeTimeline = Number.isFinite(timeline)
      ? clamp(timeline, 0, transitionCount)
      : 0;
    const activeIndex = Math.min(Math.floor(safeTimeline), projects.length - 1);
    const segmentProgress = safeTimeline - activeIndex;
    const slideProgress = ease(segmentProgress);
    const trackTimeline = Math.min(
      activeIndex + slideProgress,
      transitionCount,
    );
    const foldStrength = Math.sin(segmentProgress * Math.PI);
    const isCompactViewport = window.innerWidth <= 767;
    const outgoingScale = mix(1, isCompactViewport ? 0.7 : 0.62, foldStrength);
    const incomingScale = mix(1, isCompactViewport ? 0.82 : 0.76, foldStrength);
    const outgoingShift = mix(0, isCompactViewport ? 19 : 23, foldStrength);
    const incomingRevealStrength = clamp(
      foldStrength * 2 * (1 - segmentProgress),
    );
    const rawIncomingShift = mix(
      0,
      isCompactViewport ? -25 : -32,
      incomingRevealStrength,
    );
    const incomingShift = Math.max(rawIncomingShift, -(1 - slideProgress) * 92);
    const foldedRadius = mix(0, 26, foldStrength);
    const foldedShadowY = mix(0, 28, foldStrength);
    const foldedShadowBlur = mix(0, 70, foldStrength);
    const foldedShadowAlpha = mix(0, 0.62, foldStrength);

    track.style.transform = `translate3d(${-trackTimeline * 100}vw, 0, 0)`;

    projects.forEach((project, index) => {
      const isCurrent = index === Math.round(safeTimeline);
      const isOutgoing = index === activeIndex && segmentProgress > 0;
      const isIncoming = index === activeIndex + 1 && segmentProgress > 0;
      const isInteractive = isCurrent || isOutgoing || isIncoming;

      project.classList.toggle("is-gyu-outgoing", isOutgoing);
      project.classList.toggle("is-gyu-incoming", isIncoming);
      project.style.setProperty(
        "--gyu-fold",
        isOutgoing || isIncoming ? foldStrength.toFixed(3) : "0",
      );
      project.style.transform = "translate3d(0, 0, 0) scale(1)";
      project.style.borderRadius = "0";
      project.style.borderWidth = "0";
      project.style.boxShadow = "none";
      project.style.zIndex = "0";

      if (isOutgoing) {
        project.style.transform =
          `translate3d(${outgoingShift}vw, ${mix(0, 1.5, foldStrength)}vh, 0) ` +
          `scale(${outgoingScale}) rotateY(${mix(0, 7, foldStrength)}deg) ` +
          `rotateZ(${mix(0, 2.2, foldStrength)}deg)`;
        project.style.borderRadius = `${foldedRadius}px`;
        project.style.zIndex = "2";
      }

      if (isIncoming) {
        project.style.transform =
          `translate3d(${incomingShift}vw, ${mix(0, -1, foldStrength)}vh, ` +
          `${mix(0, 40, foldStrength)}px) ` +
          `scale(${incomingScale}) rotateY(${mix(0, -4, foldStrength)}deg) ` +
          `rotateZ(${mix(0, -0.6, foldStrength)}deg)`;
        project.style.borderRadius = `${foldedRadius}px`;
        project.style.zIndex = "3";
      }

      if (isOutgoing || isIncoming) {
        project.style.boxShadow =
          `0 ${foldedShadowY}px ${foldedShadowBlur}px ` +
          `rgba(0, 0, 0, ${foldedShadowAlpha})`;
      }

      project.style.pointerEvents = "none";
      project.setAttribute("aria-hidden", String(!isInteractive));
    });
  };

  const animate = (time) => {
    const deltaTime = Math.min((time - previousTime) / 1000, 0.05);
    const smoothing = 1 - Math.exp(-12 * deltaTime);
    previousTime = time;

    displayTimeline += (targetTimeline - displayTimeline) * smoothing;

    if (Math.abs(targetTimeline - displayTimeline) < 0.0001) {
      displayTimeline = targetTimeline;
    }

    render(displayTimeline);

    if (displayTimeline !== targetTimeline) {
      animationFrame = window.requestAnimationFrame(animate);
    } else {
      animationFrame = 0;
    }
  };

  const requestRender = () => {
    targetTimeline = getTimelinePosition();

    if (reducedMotion.matches) {
      displayTimeline = targetTimeline;
      render(displayTimeline);
      return;
    }

    if (animationFrame) return;
    previousTime = performance.now();
    animationFrame = window.requestAnimationFrame(animate);
  };

  const scrollToProject = (index) => {
    const projectIndex = clamp(index, 0, projects.length - 1);
    window.scrollTo({
      top: getStoryTop() + projectIndex * segmentLength,
      behavior: reducedMotion.matches ? "auto" : "smooth",
    });
  };

  const resetToHero = () => {
    if (animationFrame) {
      window.cancelAnimationFrame(animationFrame);
      animationFrame = 0;
    }

    targetTimeline = 0;
    displayTimeline = 0;
    render(0);
    window.scrollTo({ top: 0, left: 0, behavior: "instant" });
  };

  themeButton?.addEventListener("click", () => {
    const isDark = themeButton.getAttribute("aria-pressed") === "true";
    themeButton.setAttribute("aria-pressed", String(!isDark));
    themeButton.setAttribute(
      "aria-label",
      isDark ? "라이트 테마" : "다크 테마",
    );
    themeButton.querySelector(".gyu-theme__label").textContent = isDark
      ? "Light"
      : "Dark";
    document.body.classList.toggle("gyu-light", isDark);
  });

  document
    .querySelectorAll('a[href^="#gyu-project-"]:not(.gyu-project__button)')
    .forEach((link) => {
      link.addEventListener("click", (event) => {
        const target = document.querySelector(link.getAttribute("href"));
        const projectIndex = projects.indexOf(target);
        if (projectIndex < 0) return;
        event.preventDefault();
        scrollToProject(projectIndex);
      });
    });

  const projectButtons = projects
    .map((project, index) => {
      const button = project.querySelector(".gyu-project__button");
      if (!button) return null;
      button.dataset.gyuButtonIndex = String(index);
      button.style.pointerEvents = "auto";
      return button;
    })
    .filter(Boolean);

  const activateProjectButton = (index) => {
    const currentTimeline = getTimelinePosition();
    const isProjectSettled = Math.abs(currentTimeline - index) < 0.04;

    if (!isProjectSettled) {
      scrollToProject(index);
      return;
    }

    if (index < projects.length - 1) {
      scrollToProject(index + 1);
      return;
    }

    const nextSection = story.nextElementSibling;
    if (nextSection) {
      nextSection.scrollIntoView({
        behavior: reducedMotion.matches ? "auto" : "smooth",
        block: "start",
      });
    }
  };

  const findVisualButton = (clientX, clientY) => {
    let matchedButton = null;

    projectButtons.forEach((button) => {
      const project = button.closest(".gyu-project");
      if (project?.getAttribute("aria-hidden") === "true") return;

      const rect = button.getBoundingClientRect();
      const hitPadding = 12;
      const isInside =
        clientX >= rect.left - hitPadding &&
        clientX <= rect.right + hitPadding &&
        clientY >= rect.top - hitPadding &&
        clientY <= rect.bottom + hitPadding;

      if (isInside) matchedButton = button;
    });

    return matchedButton;
  };

  const clearVisualButtonHover = () => {
    projectButtons.forEach((button) => {
      button.classList.remove("is-gyu-pointer-hover");
    });
    stage.style.cursor = "";
  };

  stage.addEventListener("pointermove", (event) => {
    const visualButton = findVisualButton(event.clientX, event.clientY);

    projectButtons.forEach((button) => {
      button.classList.toggle("is-gyu-pointer-hover", button === visualButton);
    });
    stage.style.cursor = visualButton ? "pointer" : "";
  });

  stage.addEventListener("pointerleave", clearVisualButtonHover);

  stage.addEventListener(
    "click",
    (event) => {
      const visualButton = findVisualButton(event.clientX, event.clientY);
      if (!visualButton || event.composedPath().includes(visualButton)) return;

      event.preventDefault();
      event.stopPropagation();
      activateProjectButton(Number(visualButton.dataset.gyuButtonIndex));
    },
    true,
  );

  projectButtons.forEach((button) => {
    button.addEventListener("click", (event) => {
      event.preventDefault();
      event.stopPropagation();
      activateProjectButton(Number(button.dataset.gyuButtonIndex));
    });
  });

  const setupMvpStory = () => {
    const section = document.querySelector(".gyu-mvp");
    const serviceStage = section?.querySelector(".gyu-mvp__stage");
    const deck = serviceStage?.querySelector(".gyu-mvp__deck");
    const cards = Array.from(deck?.querySelectorAll(".gyu-service-card") ?? []);
    if (!section || !serviceStage || !deck || !cards.length) return;

    const lastIndex = cards.length - 1;
    const slots = Math.min(3, lastIndex);
    let viewportHeight = window.innerHeight;
    let cardWidth = 1;
    let cardHeight = 1;
    let stackStep = 1;
    let hold = 1;
    let travel = 1;
    let segment = 1;
    let sectionTop = 0;
    let targetPosition = 0;
    let renderedPosition = 0;
    let frame = 0;
    let previousFrameTime = 0;
    let announcedIndex = -1;
    let touchStart = null;

    const status = document.createElement("p");
    status.className = "gyu-mvp__status";
    status.setAttribute("aria-live", "polite");
    status.setAttribute("aria-atomic", "true");
    serviceStage.append(status);

    const items = cards.map((card, index) => {
      const visual = card.querySelector(".gyu-service-card__visual");
      const hero = card.querySelector(".gyu-service-card__hero");
      const image = card.querySelector(".gyu-service-card__image");
      const heading = hero.querySelector("h2");
      const title = heading.textContent.trim();
      heading.id = `gyu-service-title-${index + 1}`;
      card.setAttribute("role", "group");
      card.setAttribute("aria-roledescription", "슬라이드");
      card.setAttribute(
        "aria-label",
        `${index + 1} / ${cards.length}, ${title}`,
      );

      const tab = document.createElement("button");
      tab.type = "button";
      tab.className = "gyu-service-card__side-label";
      tab.textContent = title;
      tab.setAttribute("aria-label", `${title} 카드 보기`);
      tab.addEventListener("click", () => goToCard(index));

      visual.append(tab);
      image.decoding = "async";
      return { card, visual, hero, image, tab, title };
    });

    // One pinned stage, one scroll timeline. Card geometry never drives scrolling.
    const measure = () => {
      viewportHeight = window.innerHeight;
      const viewportWidth = document.documentElement.clientWidth;
      const compact = viewportWidth < 768;
      const gutter = clamp(viewportWidth * 0.025, 14, 42);
      const width = Math.min(viewportWidth - gutter * 2, 1800);
      stackStep = clamp(
        width * (compact ? 0.07 : 0.062),
        compact ? 20 : 40,
        96,
      );
      cardWidth = width - slots * stackStep;
      const availableHeight = Math.max(220, viewportHeight - 48);
      cardHeight = compact
        ? availableHeight
        : Math.min(availableHeight, cardWidth / 1.62);

      serviceStage.style.setProperty(
        "--gyu-service-viewport",
        `${viewportHeight}px`,
      );
      serviceStage.style.setProperty("--gyu-deck-width", `${width}px`);
      serviceStage.style.setProperty("--gyu-card-width", `${cardWidth}px`);
      serviceStage.style.setProperty("--gyu-card-height", `${cardHeight}px`);
      hold = viewportHeight * 0.3;
      travel = viewportHeight * 0.85;
      segment = hold + travel;
      section.style.height = `${viewportHeight + lastIndex * segment + hold}px`;
      sectionTop = section.getBoundingClientRect().top + window.scrollY;
    };

    const readPosition = () => {
      const sectionRect = section.getBoundingClientRect();
      sectionTop = sectionRect.top + window.scrollY;
      document.body.classList.toggle(
        "gyu-mvp-active",
        sectionRect.top <= 0 && sectionRect.bottom > 0,
      );
      const offset = clamp(window.scrollY - sectionTop, 0, lastIndex * segment);
      const index = Math.min(Math.floor(offset / segment), lastIndex);
      if (index === lastIndex) return lastIndex;
      return index + clamp((offset - index * segment - hold) / travel);
    };

    // Center the remaining deck; only upcoming cards peek on the right.
    const pose = (active, index) => {
      const rightCount = Math.min(slots, lastIndex - active);
      const frontX = (slots - rightCount) * stackStep * 0.5;
      const distance = index - active;
      const depth = Math.abs(distance);
      const scale = Math.max(0.82, 1 - depth * 0.045);
      if (distance === 0) {
        return {
          x: frontX,
          y: 0,
          scale: 1,
          opacity: 1,
          body: 1,
          tab: 0,
          blur: 0,
          brightness: 1,
        };
      }
      const visible = distance > 0 && distance <= rightCount;
      return {
        x:
          distance > 0
            ? frontX + depth * stackStep + cardWidth * (1 - scale)
            : frontX - depth * stackStep,
        y: cardHeight * (1 - scale) * 0.5,
        scale,
        opacity: visible ? 1 : 0,
        body: 0,
        tab: distance > 0 && visible ? 1 : 0,
        blur: Math.min(3.5, depth * 1.1),
        brightness: Math.max(0.55, 0.86 - depth * 0.08),
      };
    };

    const renderDeck = (position) => {
      const base = Math.min(Math.floor(position), lastIndex);
      const fraction = base < lastIndex ? position - base : 0;
      const blend = ease(fraction);
      const nextIndex = Math.min(base + 1, lastIndex);
      const settled = Math.abs(position - Math.round(position)) < 0.002;
      const active = Math.round(position);

      items.forEach((item, index) => {
        const from = pose(base, index);
        const to = pose(nextIndex, index);
        const current = {};
        Object.keys(from).forEach((key) => {
          current[key] = mix(from[key], to[key], blend);
        });
        let layer =
          index >= nextIndex
            ? 40 - (index - nextIndex)
            : 20 - (nextIndex - index);
        let angle = 0;

        if (index === base) {
          if (fraction === 0) {
            Object.assign(current, from);
            layer = 50;
          } else if (fraction < 0.74) {
            // The outgoing card leaves to the left. Its layer changes only at opacity zero.
            const departure = ease(clamp(fraction / 0.74));
            current.x = from.x - (cardWidth + stackStep) * departure;
            current.y = 12 * departure;
            current.scale = mix(1, 0.94, departure);
            current.opacity = 1 - ease(clamp((fraction - 0.3) / 0.44));
            current.body = 1 - ease(clamp(fraction / 0.55));
            current.tab = 0;
            current.blur = 3 * departure;
            current.brightness = mix(1, 0.78, departure);
            angle = -3 * departure;
            layer = 50;
          } else {
            Object.assign(current, to);
            // A departed card stays hidden instead of returning as a left stack.
            current.opacity = 0;
            layer = 19;
          }
        }

        const visible = current.opacity > 0.001;
        const frontInteractive = settled && index === active;
        const tabInteractive = settled && current.tab > 0.99 && visible;
        item.card.style.transform =
          `translate3d(${current.x.toFixed(3)}px, ${current.y.toFixed(3)}px, 0) ` +
          `rotate(${angle.toFixed(3)}deg) scale(${current.scale.toFixed(5)})`;
        item.card.style.opacity = String(current.opacity);
        item.card.style.zIndex = String(layer);
        item.card.style.visibility = visible ? "visible" : "hidden";
        item.card.setAttribute("aria-hidden", String(!visible));
        item.card.inert = !visible;
        item.image.style.filter = `blur(${current.blur.toFixed(2)}px) brightness(${current.brightness.toFixed(3)})`;
        item.image.setAttribute("aria-hidden", String(!frontInteractive));
        item.hero.style.opacity = String(current.body);
        item.hero.setAttribute("aria-hidden", String(!frontInteractive));
        item.tab.style.opacity = String(current.tab);
        item.tab.disabled = !tabInteractive;
        item.tab.tabIndex = tabInteractive ? 0 : -1;
        item.tab.style.pointerEvents = tabInteractive ? "auto" : "none";
        item.tab.setAttribute("aria-hidden", String(!tabInteractive));
      });

      if (settled && announcedIndex !== active) {
        announcedIndex = active;
        status.textContent = `${active + 1} / ${cards.length}. ${items[active].title}`;
      }
      deck.dataset.activeIndex = String(active);
      deck.dataset.position = position.toFixed(4);
    };

    const animateDeck = (time) => {
      const elapsed = Math.min(time - previousFrameTime, 50);
      previousFrameTime = time;
      const smoothing = 1 - Math.exp(-elapsed / 85);
      renderedPosition += (targetPosition - renderedPosition) * smoothing;
      if (Math.abs(targetPosition - renderedPosition) < 0.0001) {
        renderedPosition = targetPosition;
      }
      renderDeck(renderedPosition);
      frame =
        renderedPosition !== targetPosition
          ? window.requestAnimationFrame(animateDeck)
          : 0;
    };

    const requestDeckRender = () => {
      targetPosition = readPosition();
      if (reducedMotion.matches) {
        if (frame) window.cancelAnimationFrame(frame);
        frame = 0;
        renderedPosition = Math.round(targetPosition);
        renderDeck(renderedPosition);
      } else if (!frame) {
        previousFrameTime = performance.now();
        frame = window.requestAnimationFrame(animateDeck);
      }
    };

    const goToCard = (index) => {
      const destination = clamp(index, 0, lastIndex);
      const focusedControl = deck.contains(document.activeElement);
      if (focusedControl) deck.focus({ preventScroll: true });
      sectionTop = section.getBoundingClientRect().top + window.scrollY;
      window.scrollTo({
        top: sectionTop + destination * segment + hold * 0.5,
        behavior: reducedMotion.matches ? "auto" : "smooth",
      });
      requestDeckRender();
    };

    deck.addEventListener("keydown", (event) => {
      const current = Math.round(targetPosition);
      const destinations = {
        ArrowRight: current + 1,
        ArrowLeft: current - 1,
        Home: 0,
        End: lastIndex,
      };
      if (!(event.key in destinations)) return;
      event.preventDefault();
      goToCard(destinations[event.key]);
    });
    deck.addEventListener(
      "touchstart",
      (event) => {
        const touch = event.touches[0];
        touchStart = touch ? { x: touch.clientX, y: touch.clientY } : null;
      },
      { passive: true },
    );
    deck.addEventListener(
      "touchend",
      (event) => {
        const touch = event.changedTouches[0];
        if (!touch || !touchStart) return;
        const dx = touch.clientX - touchStart.x;
        const dy = touch.clientY - touchStart.y;
        touchStart = null;
        if (Math.abs(dx) > 55 && Math.abs(dx) > Math.abs(dy) * 1.5) {
          goToCard(Math.round(targetPosition) + (dx < 0 ? 1 : -1));
        }
      },
      { passive: true },
    );

    section.classList.add("is-deck");
    measure();
    targetPosition = readPosition();
    renderedPosition = reducedMotion.matches
      ? Math.round(targetPosition)
      : targetPosition;
    renderDeck(renderedPosition);
    window.addEventListener("scroll", requestDeckRender, { passive: true });
    window.addEventListener("resize", () => {
      measure();
      requestDeckRender();
    });
    window.addEventListener("pageshow", requestDeckRender);
    reducedMotion.addEventListener("change", requestDeckRender);
    document.fonts?.ready.then(requestDeckRender);
  };

  window.addEventListener("scroll", requestRender, { passive: true });
  window.addEventListener("resize", () => {
    syncStoryHeight();
    requestRender();
  });
  window.addEventListener("pageshow", (event) => {
    if (event.persisted || shouldResetOnLoad) {
      resetToHero();
      window.requestAnimationFrame(resetToHero);
      return;
    }

    requestRender();
  });
  reducedMotion.addEventListener?.("change", requestRender);

  prepareProjects();
  syncStoryHeight();

  if (shouldResetOnLoad) {
    if (window.location.hash) {
      window.history.replaceState(
        window.history.state,
        "",
        window.location.pathname + window.location.search,
      );
    }
    resetToHero();
  } else {
    targetTimeline = getTimelinePosition();
    displayTimeline = targetTimeline;
    render(displayTimeline);
  }

  setupMvpStory();
})();
// ===================== Gyu Lane End =====================

// ==================== Suro Lane Start ====================
(() => {
gsap.registerPlugin(ScrollTrigger);

// 호버 마퀴
const marqueeBtns = document.querySelectorAll(".marquee-btn");

marqueeBtns.forEach((btn) => {
  const originalText = btn.querySelector(".marquee-text");

  if (!originalText) return;

  const text = originalText.textContent.trim();
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

  if (btn.querySelector(".marquee-viewport")) return;

  const viewport = document.createElement("span");
  viewport.classList.add("marquee-viewport");
  viewport.setAttribute("aria-hidden", "true");

  const track = document.createElement("span");
  track.classList.add("marquee-track");

  viewport.appendChild(track);
  btn.appendChild(viewport);

  const createItem = () => {
    const item = document.createElement("span");

    item.classList.add("marquee-item");
    item.textContent = text;

    return item;
  };

  const firstItem = createItem();
  track.appendChild(firstItem);

  for (let i = 1; i < 3; i++) {
    track.appendChild(createItem());
  }

  const marqueeTween = gsap.to(track, {
    x: () => -firstItem.getBoundingClientRect().width,
    duration: 1.5,
    ease: "none",
    repeat: -1,
    paused: true,
  });

  const startMarquee = () => {
    if (!reducedMotion.matches) marqueeTween.invalidate().restart();
  };

  const stopMarquee = () => {
    if (btn.matches(":hover, :focus-visible")) return;
    marqueeTween.pause();
    gsap.set(track, { x: 0 });
  };

  btn.addEventListener("mouseenter", startMarquee);
  btn.addEventListener("mouseleave", stopMarquee);
  btn.addEventListener("focus", startMarquee);
  btn.addEventListener("blur", stopMarquee);
});

// why-sec

const section = document.querySelector(".why-sec");
const items = gsap.utils.toArray(".why-item");

const ITEM_WIDTH = 820;
const STACK_WIDTH = 160;
const MOVE_DISTANCE = ITEM_WIDTH - STACK_WIDTH;

items.forEach((item, i) => {
  gsap.set(item, {
    x: i * ITEM_WIDTH,
    zIndex: i + 1,
    width: ITEM_WIDTH,
  });
});

const tl = gsap.timeline({
  scrollTrigger: {
    trigger: section,
    start: "top top",
    end: () => `+=${window.innerHeight * (items.length - 1)}`,
    pin: true,
    scrub: 0.2,
    anticipatePin: 0,
    invalidateOnRefresh: true,
  },
});

for (let i = 1; i < items.length; i++) {
  const movingItems = items.slice(i);
  const prevBottom = items[i - 1].querySelector(".why-item-bottom");

  tl.to(movingItems, {
    x: `-=${MOVE_DISTANCE}`,
    duration: 1,
    ease: "none",
  });

  tl.to(
    prevBottom,
    {
      opacity: 0,
      duration: 0.65,
      ease: "none",
    },
    "<0.15",
  );
}

// contact-sec

const contactSection = document.querySelector(".contact-sec");
if (!contactSection) return;
const contactRight = contactSection.querySelector(".right-box");
const contactTrack = contactSection.querySelector(".contact-form-track");

const getContactMove = () => {
  return Math.max(0, contactTrack.scrollHeight - contactRight.clientHeight);
};

const contactTl = gsap.timeline({
  scrollTrigger: {
    trigger: contactSection,
    start: "top top",
    end: () => `+=${Math.max(getContactMove() * 1.5, window.innerHeight)}`,
    pin: true,
    scrub: 0.2,
    anticipatePin: 0,
    invalidateOnRefresh: true,
    // markers: true,
  },
});

contactTl.to(
  contactTrack,
  {
    y: () => -getContactMove(),
    ease: "none",
  },
  0,
);

const contactBg = contactSection.querySelector(".contact-bg");

contactTl.fromTo(
  contactBg,
  {
    yPercent: 5,
  },
  {
    yPercent: -5,
    ease: "none",
  },
  0,
);
})();
// ===================== Suro Lane End =====================
