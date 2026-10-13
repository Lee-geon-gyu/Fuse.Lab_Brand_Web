// 관리자 표 게시판 편집 (admin/board-table.php). 표 상태를 메모리에 두고 다시 그린 뒤, 저장 시 JSON 으로 보낸다
(() => {
  const form = document.querySelector("#table-form");
  const editor = document.querySelector("#table-editor");
  if (!form || !editor) return;

  const head = editor.tHead;
  const body = editor.tBodies[0];
  const toast = document.querySelector("#board-toast");
  const limits = {
    columns: Number(editor.dataset.maxColumns),
    rows: Number(editor.dataset.maxRows),
    columnName: Number(editor.dataset.columnMax),
    cell: Number(editor.dataset.cellMax),
  };
  const state = JSON.parse(document.querySelector("#table-data").textContent);
  let dirty = false;
  let toastTimer;

  function notify(message) {
    toast.textContent = message;
    toast.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toast.hidden = true; }, 3000);
  }

  function button(label, action, index, ariaLabel, disabled = false) {
    const element = document.createElement("button");
    element.type = "button";
    element.className = "order-move";
    element.textContent = label;
    element.dataset.action = action;
    element.dataset.index = String(index);
    element.setAttribute("aria-label", ariaLabel);
    element.disabled = disabled;
    return element;
  }

  // 모든 값은 value/textContent 로만 넣는다 (입력값이 HTML 로 해석되지 않도록)
  function render() {
    const { columns, rows } = state;
    head.replaceChildren();
    body.replaceChildren();
    document.querySelector("#column-count").textContent = columns.length;
    document.querySelector("#row-count").textContent = rows.length;

    if (!columns.length) {
      const tr = body.insertRow();
      tr.className = "empty-row";
      const td = tr.insertCell();
      td.textContent = "열 추가를 눌러 표를 시작하세요.";
      return;
    }

    const headRow = head.insertRow();
    const numberHead = document.createElement("th");
    numberHead.scope = "col";
    numberHead.className = "table-editor__no";
    numberHead.textContent = "No";
    headRow.append(numberHead);
    columns.forEach((name, index) => {
      const th = document.createElement("th");
      th.scope = "col";
      const wrap = document.createElement("div");
      wrap.className = "table-editor__column";
      const input = document.createElement("input");
      input.className = "table-editor__column-name";
      input.value = name;
      input.maxLength = limits.columnName;
      input.placeholder = "열 이름";
      input.title = name;
      input.dataset.column = String(index);
      input.setAttribute("aria-label", `${index + 1}번째 열 이름`);
      const controls = document.createElement("div");
      controls.className = "order-controls";
      controls.append(
        button("◀", "column-left", index, `${index + 1}번째 열 왼쪽으로`, index === 0),
        button("▶", "column-right", index, `${index + 1}번째 열 오른쪽으로`, index === columns.length - 1),
        button("✕", "column-delete", index, `${index + 1}번째 열 삭제`),
      );
      wrap.append(input, controls);
      th.append(wrap);
      headRow.append(th);
    });
    const manageHead = document.createElement("th");
    manageHead.scope = "col";
    manageHead.className = "table-editor__manage";
    manageHead.textContent = "관리";
    headRow.append(manageHead);

    if (!rows.length) {
      const tr = body.insertRow();
      tr.className = "empty-row";
      const td = tr.insertCell();
      td.colSpan = columns.length + 2;
      td.textContent = "행 추가를 눌러 내용을 입력하세요.";
      return;
    }

    rows.forEach((cells, rowIndex) => {
      const tr = body.insertRow();
      tr.insertCell().textContent = String(rowIndex + 1);
      columns.forEach((_, columnIndex) => {
        const textarea = document.createElement("textarea");
        textarea.className = "table-editor__cell";
        textarea.rows = 1;
        textarea.maxLength = limits.cell;
        textarea.value = cells[columnIndex] ?? "";
        textarea.dataset.row = String(rowIndex);
        textarea.dataset.column = String(columnIndex);
        textarea.setAttribute("aria-label", `${rowIndex + 1}행 ${columns[columnIndex] || columnIndex + 1}`);
        tr.insertCell().append(textarea);
      });
      const controls = document.createElement("div");
      controls.className = "order-controls";
      controls.append(
        button("▲", "row-up", rowIndex, `${rowIndex + 1}행 위로`, rowIndex === 0),
        button("▼", "row-down", rowIndex, `${rowIndex + 1}행 아래로`, rowIndex === rows.length - 1),
        button("✕", "row-delete", rowIndex, `${rowIndex + 1}행 삭제`),
      );
      tr.insertCell().append(controls);
    });
    body.querySelectorAll(".table-editor__cell").forEach(autoGrow);
  }

  // 칸 높이를 내용에 맞춘다 (긴 내용·줄바꿈이 잘리지 않게)
  function autoGrow(textarea) {
    textarea.style.height = "auto";
    textarea.style.height = `${textarea.scrollHeight}px`;
  }

  function swap(list, from, to) {
    if (to < 0 || to >= list.length) return;
    [list[from], list[to]] = [list[to], list[from]];
  }

  function change(mutate) {
    mutate();
    dirty = true;
    render();
  }

  document.querySelector("#add-column").addEventListener("click", () => {
    if (state.columns.length >= limits.columns) {
      notify(`열은 ${limits.columns}개까지 만들 수 있습니다.`);
      return;
    }
    change(() => {
      state.columns.push(`열 ${state.columns.length + 1}`);
      state.rows.forEach((cells) => cells.push(""));
    });
    head.querySelector(`[data-column="${state.columns.length - 1}"]`)?.select();
  });

  document.querySelector("#add-row").addEventListener("click", () => {
    if (!state.columns.length) {
      notify("먼저 열을 추가해 주세요.");
      return;
    }
    if (state.rows.length >= limits.rows) {
      notify(`행은 ${limits.rows}개까지 저장할 수 있습니다.`);
      return;
    }
    change(() => state.rows.push(state.columns.map(() => "")));
    body.querySelector(`[data-row="${state.rows.length - 1}"]`)?.focus();
  });

  editor.addEventListener("click", (event) => {
    const target = event.target.closest("button[data-action]");
    if (!target) return;
    const index = Number(target.dataset.index);
    const { columns, rows } = state;
    switch (target.dataset.action) {
      case "column-left":
      case "column-right": {
        const to = target.dataset.action === "column-left" ? index - 1 : index + 1;
        change(() => {
          swap(columns, index, to);
          rows.forEach((cells) => swap(cells, index, to));
        });
        break;
      }
      case "column-delete":
        if (!confirm(`'${columns[index]}' 열과 그 열의 내용을 삭제할까요? 저장해야 반영됩니다.`)) return;
        change(() => {
          columns.splice(index, 1);
          rows.forEach((cells) => cells.splice(index, 1));
        });
        break;
      case "row-up":
      case "row-down":
        change(() => swap(rows, index, target.dataset.action === "row-up" ? index - 1 : index + 1));
        break;
      case "row-delete":
        change(() => rows.splice(index, 1));
        break;
    }
  });

  editor.addEventListener("input", (event) => {
    const target = event.target;
    if (target.matches(".table-editor__column-name")) {
      state.columns[Number(target.dataset.column)] = target.value;
      target.title = target.value;
    } else if (target.matches(".table-editor__cell")) {
      state.rows[Number(target.dataset.row)][Number(target.dataset.column)] = target.value;
      autoGrow(target);
    } else {
      return;
    }
    dirty = true;
  });

  form.addEventListener("submit", (event) => {
    const emptyColumn = state.columns.findIndex((name) => !name.trim());
    if (emptyColumn !== -1) {
      event.preventDefault();
      head.querySelector(`[data-column="${emptyColumn}"]`)?.focus();
      notify("열 이름을 입력해 주세요.");
      return;
    }
    document.querySelector("#table-input").value = JSON.stringify(state);
    dirty = false;
  });

  window.addEventListener("beforeunload", (event) => {
    if (dirty) event.preventDefault();
  });

  if (toast.dataset.autoHide !== undefined) {
    toastTimer = setTimeout(() => { toast.hidden = true; }, 3000);
  }
  render();
})();
