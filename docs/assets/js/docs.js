
(() => {
  const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

  const normalize = (value) => String(value ?? '')
    .toLowerCase()
    .replaceAll('ё', 'е')
    .replace(/\\/g, ' ')
    .replace(/[(){}\[\].,;:<>"'`|/+*=!?\-]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();

  const getSnippet = (content, query) => {
    const source = String(content ?? '');
    const plain = source.replace(/\s+/g, ' ').trim();
    if (!plain) return '';
    const q = normalize(query).split(' ').filter(Boolean)[0] || '';
    const idx = q ? normalize(plain).indexOf(q) : -1;
    if (idx < 0) return plain.slice(0, 170) + (plain.length > 170 ? '…' : '');
    const start = Math.max(0, idx - 70);
    const end = Math.min(plain.length, idx + 140);
    return (start > 0 ? '…' : '') + plain.slice(start, end) + (end < plain.length ? '…' : '');
  };

  const scoreItem = (item, query) => {
    const q = normalize(query);
    if (q.length < 1) return 0;
    const terms = q.split(' ').filter(Boolean);
    const title = normalize(item.title);
    const section = normalize(item.section);
    const type = normalize(item.type);
    const url = normalize(item.url);
    const content = normalize(item.content);
    let score = 0;
    for (const term of terms) {
      if (!term) continue;
      if (title === term || type === term) score += 120;
      if (title.includes(term)) score += 70;
      if (type.includes(term)) score += 55;
      if (section.includes(term)) score += 30;
      if (url.includes(term)) score += 25;
      if (content.includes(term)) score += 12;
    }
    const exact = q.length > 1;
    if (exact && title.includes(q)) score += 110;
    if (exact && type.includes(q)) score += 85;
    if (exact && content.includes(q)) score += 30;
    return score;
  };

  window.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const btn = document.getElementById('mobileMenuBtn');
    const backdrop = document.getElementById('mobileBackdrop');
    const openSidebar = () => { sidebar?.classList.add('show'); backdrop?.classList.add('show'); };
    const closeSidebar = () => { sidebar?.classList.remove('show'); backdrop?.classList.remove('show'); };
    btn?.addEventListener('click', openSidebar);
    backdrop?.addEventListener('click', closeSidebar);
    document.querySelectorAll('.sidebar .nav-link[href]').forEach(link => link.addEventListener('click', closeSidebar));

    const modal = document.getElementById('docsSearchModal');
    const input = document.getElementById('docsSearchInput');
    const results = document.getElementById('docsSearchResults');
    const openBtn = document.getElementById('docsSearchOpen');
    const prefix = document.body.dataset.rootPrefix || '';
    const index = window.AIRCMS_SEARCH_INDEX || [];
    let currentItems = [];
    let selected = 0;

    const render = () => {
      if (!results) return;
      if (!currentItems.length) {
        const hasQuery = (input?.value || '').trim().length > 0;
        results.innerHTML = `<div class="search-empty">${hasQuery ? 'Ничего не найдено' : 'Начни вводить название класса, метода, секции или фразу из примера кода'}</div>`;
        return;
      }
      results.innerHTML = currentItems.slice(0, 5).map((item, i) => `
        <a class="search-dialog-result${i === selected ? ' active' : ''}" href="${prefix}${escapeHtml(item.url)}" role="option" aria-selected="${i === selected ? 'true' : 'false'}" data-search-index="${i}">
          <div class="search-result-title">${escapeHtml(item.title)}</div>
          <div class="search-result-meta">${escapeHtml(item.section)} · ${escapeHtml(item.type)}</div>
          <div class="search-result-snippet">${escapeHtml(getSnippet(item.content, input?.value || ''))}</div>
        </a>
      `).join('');
    };

    const update = () => {
      const q = input?.value || '';
      const nq = normalize(q);
      if (nq.length < 1) {
        currentItems = [];
        selected = 0;
        render();
        return;
      }
      currentItems = index
        .map(item => ({...item, _score: scoreItem(item, q)}))
        .filter(item => item._score > 0)
        .sort((a, b) => b._score - a._score || String(a.title).localeCompare(String(b.title), 'ru'))
        .slice(0, 5);
      selected = 0;
      render();
    };

    const openSearch = () => {
      if (!modal || !input) return;
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('search-open');
      input.value = '';
      currentItems = [];
      selected = 0;
      render();
      window.setTimeout(() => input.focus(), 30);
    };

    const closeSearch = () => {
      if (!modal) return;
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('search-open');
    };

    openBtn?.addEventListener('click', openSearch);
    input?.addEventListener('input', update);
    results?.addEventListener('mousemove', (e) => {
      const link = e.target.closest('[data-search-index]');
      if (!link) return;
      selected = Number(link.dataset.searchIndex || 0);
      render();
    });

    document.querySelectorAll('[data-search-close]').forEach(el => el.addEventListener('click', closeSearch));

    document.addEventListener('keydown', (e) => {
      const isSearchShortcut = (e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k';
      if (isSearchShortcut) {
        e.preventDefault();
        openSearch();
        return;
      }
      if (!modal?.classList.contains('is-open')) return;
      if (e.key === 'Escape') {
        e.preventDefault();
        closeSearch();
        return;
      }
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (currentItems.length) {
          selected = (selected + 1) % currentItems.length;
          render();
        }
        return;
      }
      if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (currentItems.length) {
          selected = (selected - 1 + currentItems.length) % currentItems.length;
          render();
        }
        return;
      }
      if (e.key === 'Enter') {
        if (currentItems[selected]) {
          e.preventDefault();
          window.location.href = prefix + currentItems[selected].url;
        }
      }
    });
  });
})();
