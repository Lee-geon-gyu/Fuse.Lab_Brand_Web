// sub-pages의 HTML 미리보기: 서버 요청 없이 분류와 더보기 처리.
(() => {
  const root = document.querySelector('[data-portfolio-static]');
  if (!root) return;

  const rows = root.querySelector('[data-portfolio-rows]');
  const cards = [...rows.querySelectorAll('.body-list-item')];
  const buttons = [...root.querySelectorAll('.filter-list [data-category]')];
  const more = root.querySelector('[data-portfolio-more]');
  const empty = root.querySelector('[data-portfolio-empty]');
  const params = new URLSearchParams(location.search);
  let category = params.get('category') || '';
  if (!buttons.some((button) => button.dataset.category === category)) category = '';
  let limit = Math.min(cards.length, Math.max(1, Number(params.get('page')) || 1) * 6);

  function render() {
    const matches = cards.filter((card) => !category || card.dataset.category === category);
    const visible = matches.slice(0, limit);
    rows.replaceChildren();
    for (let start = 0; start < visible.length; start += 3) {
      const row = document.createElement('div');
      row.className = (start / 3) % 2 === 0 ? 'top-box' : 'bottom-box';
      const list = document.createElement('ul');
      list.className = 'body-list';
      visible.slice(start, start + 3).forEach((card, index) => {
        card.classList.toggle('margin-item', index === 1);
        list.append(card);
      });
      row.append(list);
      rows.append(row);
    }
    empty.hidden = matches.length > 0;
    more.hidden = visible.length >= matches.length;
    buttons.forEach((button) => {
      const active = button.dataset.category === category;
      button.classList.toggle('active', active);
      button.setAttribute('aria-pressed', String(active));
    });
  }

  root.querySelector('form').addEventListener('submit', (event) => event.preventDefault());
  buttons.forEach((button) => button.addEventListener('click', () => {
    category = button.dataset.category;
    limit = 6;
    render();
  }));
  more.addEventListener('click', () => {
    limit += 6;
    render();
  });
  render();
})();
