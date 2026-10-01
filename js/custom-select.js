(() => {
  const instances = [];

  function enhanceSelect(box) {
    const select = box.querySelector("select");
    const arrow = box.querySelector("img");
    if (!select || !arrow) return;

    const trigger = document.createElement("button");
    trigger.type = "button";
    trigger.className = "select-box__trigger";
    trigger.setAttribute("aria-haspopup", "listbox");
    trigger.setAttribute("aria-expanded", "false");
    const accessibleLabel = select.getAttribute("aria-label") ||
      document.querySelector(`label[for="${select.id}"]`)?.textContent.trim() || "선택";
    trigger.setAttribute("aria-label", accessibleLabel);

    const text = document.createElement("span");
    trigger.append(text, arrow);

    const surface = document.createElement("div");
    surface.className = "select-box__surface";

    const menu = document.createElement("div");
    menu.className = "select-box__menu";
    menu.id = `${select.id}-listbox`;
    menu.setAttribute("role", "listbox");
    menu.setAttribute("aria-label", accessibleLabel);
    menu.setAttribute("aria-hidden", "true");
    menu.inert = true;
    trigger.setAttribute("aria-controls", menu.id);

    const options = Array.from(select.options).map((nativeOption) => {
      const option = document.createElement("button");
      option.type = "button";
      option.className = "select-box__option";
      option.setAttribute("role", "option");
      option.dataset.value = nativeOption.value;
      option.textContent = nativeOption.textContent;
      option.tabIndex = -1;
      menu.append(option);
      return option;
    });
    let closeTimer;
    let focusTimer;
    let highlightedOption;

    function highlight(option) {
      if (!option || option === highlightedOption) return;
      highlightedOption?.classList.remove("is-highlighted");
      option.classList.add("is-highlighted");
      highlightedOption = option;
    }

    options.forEach((option) => {
      option.addEventListener("pointerenter", () => {
        clearTimeout(focusTimer);
        highlight(option);
      });
      option.addEventListener("focus", () => highlight(option));
    });

    function sync() {
      const selected = select.selectedOptions[0];
      text.textContent = selected?.textContent || "선택";
      trigger.setAttribute("aria-label", `${accessibleLabel}: ${text.textContent}`);
      options.forEach((option) => {
        option.setAttribute("aria-selected", String(option.dataset.value === select.value));
      });
    }

    function close(restoreFocus = false) {
      if (!box.classList.contains("is-open")) return;
      box.classList.remove("is-open");
      box.classList.add("is-closing");
      menu.setAttribute("aria-hidden", "true");
      menu.inert = true;
      trigger.setAttribute("aria-expanded", "false");
      clearTimeout(closeTimer);
      clearTimeout(focusTimer);
      closeTimer = setTimeout(() => {
        box.classList.remove("is-closing");
      }, matchMedia("(prefers-reduced-motion: reduce)").matches ? 0 : 350);
      if (restoreFocus) trigger.focus();
    }

    function open(focusIndex) {
      instances.forEach((instance) => {
        if (instance.box !== box) instance.close();
      });
      clearTimeout(closeTimer);
      clearTimeout(focusTimer);
      box.classList.remove("is-closing");
      box.classList.add("is-open");
      menu.setAttribute("aria-hidden", "false");
      menu.inert = false;
      trigger.setAttribute("aria-expanded", "true");
      highlight(options[focusIndex ?? Math.max(0, select.selectedIndex)]);
      if (focusIndex !== undefined) {
        focusTimer = setTimeout(() => {
          if (box.classList.contains("is-open")) options[focusIndex]?.focus();
        }, matchMedia("(prefers-reduced-motion: reduce)").matches ? 0 : 350);
      }
    }

    function choose(option) {
      select.value = option.dataset.value;
      sync();
      select.dispatchEvent(new Event("change", { bubbles: true }));
      close(true);
    }

    trigger.addEventListener("click", () => {
      if (box.classList.contains("is-open")) close();
      else open();
    });

    trigger.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        close();
        return;
      }
      if (!["ArrowDown", "ArrowUp", "Home", "End"].includes(event.key)) return;
      event.preventDefault();
      if (event.key === "Home") open(0);
      else if (event.key === "End") open(options.length - 1);
      else open(Math.max(0, Math.min(options.length - 1,
        select.selectedIndex + (event.key === "ArrowDown" ? 1 : -1))));
    });

    menu.addEventListener("click", (event) => {
      const option = event.target.closest(".select-box__option");
      if (option) choose(option);
    });

    menu.addEventListener("keydown", (event) => {
      const current = event.target.closest(".select-box__option");
      if (!current) return;
      const index = options.indexOf(current);
      if (event.key === "Escape") {
        event.preventDefault();
        close(true);
      } else if (event.key === "Tab") {
        close();
      } else if (["ArrowDown", "ArrowUp", "Home", "End"].includes(event.key)) {
        event.preventDefault();
        const next = event.key === "Home" ? 0
          : event.key === "End" ? options.length - 1
            : (index + (event.key === "ArrowDown" ? 1 : -1) + options.length) % options.length;
        options[next].focus();
      }
    });

    document.addEventListener("pointerdown", (event) => {
      if (!box.contains(event.target)) close();
    });
    select.addEventListener("change", sync);

    surface.append(trigger, menu);
    box.append(surface);
    box.style.setProperty("--select-menu-height", `${menu.scrollHeight}px`);
    box.classList.add("is-enhanced");
    sync();
    instances.push({ box, close });
  }

  document.querySelectorAll("[data-custom-select]").forEach(enhanceSelect);
})();
