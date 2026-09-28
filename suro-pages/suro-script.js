gsap.registerPlugin(ScrollTrigger);

// 호버 마퀴
const marqueeBtns = document.querySelectorAll(".marquee-btn");

marqueeBtns.forEach((btn) => {
  const originalText = btn.querySelector(".marquee-text");

  if (!originalText) return;

  const text = originalText.textContent.trim();
  const buttonWidth = btn.getBoundingClientRect().width;

  const track = document.createElement("span");
  track.classList.add("marquee-track");

  btn.style.width = `${buttonWidth}px`;
  originalText.replaceWith(track);

  const createItem = () => {
    const item = document.createElement("span");

    item.classList.add("marquee-item");
    item.textContent = text;

    return item;
  };

  const firstItem = createItem();
  track.appendChild(firstItem);

  const itemWidth = firstItem.getBoundingClientRect().width;
  const itemCount = Math.max(2, Math.ceil((buttonWidth * 2) / itemWidth));

  for (let i = 1; i < itemCount; i++) {
    track.appendChild(createItem());
  }

  const originalItems = [...track.children];

  originalItems.forEach((item) => {
    const clone = item.cloneNode(true);

    clone.setAttribute("aria-hidden", "true");

    track.appendChild(clone);
  });

  const moveWidth = track.scrollWidth / 2;

  const marqueeTween = gsap.to(track, {
    x: -moveWidth,
    duration: 3,
    ease: "none",
    repeat: -1,
    paused: true,
  });

  btn.addEventListener("mouseenter", () => {
    marqueeTween.restart();
  });

  btn.addEventListener("mouseleave", () => {
    marqueeTween.pause();
    gsap.set(track, { x: 0 });
  });
});

// footer text rolling
const footerNavLinks = document.querySelectorAll(".footer-nav a");

footerNavLinks.forEach((link) => {
  const label = link.textContent.trim();
  link.textContent = "";

  const clip = document.createElement("span");
  clip.className = "rolling-label";

  [...label].forEach((character, index) => {
    const column = document.createElement("span");
    column.className = "rolling-character";
    column.style.setProperty("--index", index);

    const stack = document.createElement("span");
    stack.className = "rolling-stack";

    for (let i = 0; i < 2; i++) {
      const glyph = document.createElement("span");
      glyph.textContent = character === " " ? "\u00a0" : character;
      stack.append(glyph);
    }

    column.append(stack);
    clip.append(column);
  });

  link.append(clip);
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

