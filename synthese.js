// Tableau de synthèse: highlight one competence (chips, column headers, keys 1–6 / 0),
// column hover crosshair and full screen, to read and present the table more easily.
(() => {
  const section = document.getElementById('synthese');
  const table = section?.querySelector('.synth-table');
  if (!table) return;
  section.classList.add('synth-ready');

  const chips = [...section.querySelectorAll('.synth-chips [data-focus]')];
  const panels = [...section.querySelectorAll('.synth-focus')];
  const rows = [...table.querySelectorAll('tr[data-covers]')];
  const colCells = [...table.querySelectorAll('[data-col]')];
  let focus = 0;

  const setFocus = n => {
    focus = n;
    table.classList.toggle('has-focus', n > 0);
    chips.forEach(chip => chip.setAttribute('aria-pressed', String(Number(chip.dataset.focus) === n)));
    panels.forEach(panel => { panel.hidden = Number(panel.dataset.panel) !== n; });
    // phones: keep the active chip visible in its horizontal row
    const active = chips.find(chip => Number(chip.dataset.focus) === n);
    const chipRow = active?.parentElement;
    if (chipRow && chipRow.scrollWidth > chipRow.clientWidth) {
      chipRow.scrollTo({ left: active.offsetLeft - chipRow.offsetLeft - 22, behavior: 'smooth' });
    }
    colCells.forEach(cell => cell.classList.toggle('is-col-focus', Number(cell.dataset.col) === n));
    rows.forEach(row => {
      const covers = row.dataset.covers.split(' ').map(Number);
      row.classList.toggle('is-match', n > 0 && covers.includes(n));
      row.classList.toggle('is-dim', n > 0 && !covers.includes(n));
    });
  };

  section.querySelector('.synth-chips').addEventListener('click', event => {
    const chip = event.target.closest('[data-focus]');
    if (chip) setFocus(Number(chip.dataset.focus));
  });

  // clicking a competence column header toggles its highlight
  table.querySelectorAll('thead th[data-col]').forEach(th => {
    th.tabIndex = 0;
    th.setAttribute('role', 'button');
    const toggle = () => setFocus(focus === Number(th.dataset.col) ? 0 : Number(th.dataset.col));
    th.addEventListener('click', toggle);
    th.addEventListener('keydown', event => {
      if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); toggle(); }
    });
  });

  // crosshair: the hovered column lights up from header to total
  let hoverCol = null;
  const setHover = col => {
    if (col === hoverCol) return;
    hoverCol = col;
    colCells.forEach(cell => cell.classList.toggle('is-col-hover', cell.dataset.col === col));
  };
  table.addEventListener('pointerover', event => setHover(event.target.closest('[data-col]')?.dataset.col ?? null));
  table.addEventListener('pointerleave', () => setHover(null));

  // keys 1–6 and 0 while the table is on screen (physical digit keys, so AZERTY works without Shift)
  const onScreen = () => {
    const rect = section.getBoundingClientRect();
    return rect.top < window.innerHeight * 0.6 && rect.bottom > window.innerHeight * 0.4;
  };
  document.addEventListener('keydown', event => {
    if (event.altKey || event.ctrlKey || event.metaKey || event.defaultPrevented) return;
    if (event.target.closest('input, textarea, select, [contenteditable="true"]')) return;
    if (document.querySelector('dialog[open]')) return;
    const match = /^(?:Digit|Numpad)([0-9])$/.exec(event.code);
    if (!match || !(onScreen() || document.fullscreenElement === section)) return;
    const n = Number(match[1]);
    if (n > chips.length - 1) return;
    event.preventDefault();
    setFocus(n);
  });

  // full screen on the section itself
  const fullButton = section.querySelector('[data-synth-full]');
  if (fullButton && section.requestFullscreen) {
    const label = fullButton.querySelector('span');
    fullButton.addEventListener('click', () => {
      if (document.fullscreenElement === section) document.exitFullscreen();
      else section.requestFullscreen().catch(() => {});
    });
    document.addEventListener('fullscreenchange', () => {
      const on = document.fullscreenElement === section;
      fullButton.setAttribute('aria-pressed', String(on));
      label.textContent = on ? 'Quitter le plein écran' : 'Plein écran';
    });
  } else if (fullButton) {
    fullButton.hidden = true;
  }

  // ---------- loupe: hovering (or focusing) a row shows a large, readable card next to the table,
  // with the cover, the competences in words, the summary and a direct link to the article.
  // The whole row opens the article. Shown at real size even when the section is scaled down.
  let preview = {};
  try { preview = JSON.parse(document.getElementById('synthPreviewData')?.textContent || '{}'); } catch { preview = {}; }
  const articleRows = rows.filter(row => row.dataset.article);

  // the whole row is clickable: it opens the article through the existing link
  articleRows.forEach(row => {
    row.classList.add('is-linked');
    row.addEventListener('click', event => {
      if (event.target.closest('a')) return;
      row.querySelector('th a')?.click();
    });
  });

  // the details panel sits beside the table on a wide screen and under it otherwise (synth-layout in
  // style.css): it never covers the ticks. It shows the hovered or focused row and keeps it until another one.
  const wrap = section.querySelector('.synth-wrap');
  const layout = document.createElement('div');
  layout.className = 'synth-layout';
  wrap.before(layout);
  const card = document.createElement('aside');
  card.className = 'synth-loupe';
  card.setAttribute('aria-live', 'polite');
  layout.append(wrap, card);
  const competences = preview.competences || [];
  let shownRow = null;

  const esc = text => String(text ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);
  // built once, then only its texts and picture change: no flicker when moving from row to row
  card.innerHTML = `
    <div class="synth-loupe-media"><img alt=""><img alt=""></div>
    <div class="synth-loupe-body">
      <p class="synth-loupe-meta">Détail d’une réalisation</p>
      <h3 class="synth-loupe-title">Survolez une ligne du tableau</h3>
      <ul class="synth-loupe-comps"></ul>
      <p class="synth-loupe-summary">Sa fiche s’affiche ici : image, compétences en toutes lettres et résumé. Un clic sur la ligne ouvre l’article.</p>
      <button type="button" class="synth-loupe-cta" hidden>Lire l’article <span aria-hidden="true">→</span></button>
    </div>`;
  card.classList.add('is-empty');
  const [imgA, imgB] = card.querySelectorAll('.synth-loupe-media img');
  const body = card.querySelector('.synth-loupe-body');
  const meta = card.querySelector('.synth-loupe-meta');
  const title = card.querySelector('.synth-loupe-title');
  const comps = card.querySelector('.synth-loupe-comps');
  const summary = card.querySelector('.synth-loupe-summary');
  const cta = card.querySelector('.synth-loupe-cta');
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
  let front = imgA;

  // covers are loaded ahead the first time the pointer comes over the table, so the change is instant
  let preloaded = false;
  table.addEventListener('pointerenter', () => {
    if (preloaded) return;
    preloaded = true;
    Object.values(preview.articles || {}).forEach(a => { if (a.cover) { const i = new Image(); i.src = a.cover; } });
  });

  const setCover = src => {
    const media = card.querySelector('.synth-loupe-media');
    media.hidden = !src;
    if (!src || front.getAttribute('src') === src) return;
    // cross-fade: the hidden picture gets the new cover, then fades in over the current one
    const back = front === imgA ? imgB : imgA;
    const swap = () => {
      back.onload = null;
      if (back.getAttribute('src') !== src || front === back) return; // a newer cover was asked meanwhile
      back.classList.add('is-front');
      front.classList.remove('is-front');
      front = back;
    };
    back.onload = swap;
    back.src = src;
    if (back.complete && back.naturalWidth) swap();
  };
  const fill = row => {
    const data = preview.articles?.[row.dataset.article] || {};
    const covers = row.dataset.covers.split(' ').map(Number);
    card.classList.remove('is-empty');
    setCover(data.cover);
    meta.textContent = `${row.dataset.period.replace(/^Réalisations en cours de /, '')}${data.date ? ` · ${data.date}` : ''}`;
    title.textContent = data.title || row.querySelector('th a')?.textContent || '';
    comps.innerHTML = covers.map(c => `<li class="${c === focus ? 'is-focus' : ''}"><span>C${c}</span>${esc(competences[c - 1] || '')}</li>`).join('');
    summary.textContent = data.summary || '';
    cta.hidden = false;
    cta.dataset.openArticle = row.dataset.article;
    if (!reduce.matches) body.animate([{ opacity: 0.35, transform: 'translateY(4px)' }, { opacity: 1, transform: 'none' }], { duration: 180, easing: 'cubic-bezier(0.23,1,0.32,1)' });
  };
  const show = row => {
    if (shownRow === row) return;
    shownRow?.classList.remove('is-loupe');
    shownRow = row;
    row.classList.add('is-loupe');
    fill(row);
  };

  articleRows.forEach(row => {
    row.addEventListener('pointerenter', () => show(row));
    row.querySelector('th a')?.addEventListener('focus', () => show(row));
  });
})();
