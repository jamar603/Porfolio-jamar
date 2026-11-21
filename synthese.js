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

  const canHover = window.matchMedia('(hover: hover) and (pointer: fine)');
  const card = document.createElement('div');
  card.className = 'synth-loupe';
  card.setAttribute('aria-hidden', 'true');
  card.hidden = true;
  const competences = preview.competences || [];
  let shownRow = null, hideTimer = 0;

  const esc = text => String(text ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);
  const fill = row => {
    const data = preview.articles?.[row.dataset.article] || {};
    const covers = row.dataset.covers.split(' ').map(Number);
    card.innerHTML = `
      ${data.cover ? `<img class="synth-loupe-cover" src="${esc(data.cover)}" alt="">` : ''}
      <div class="synth-loupe-body">
        <p class="synth-loupe-meta">${esc(row.dataset.period.replace(/^Réalisations en cours de /, ''))}${data.date ? ` · ${esc(data.date)}` : ''}</p>
        <h3 class="synth-loupe-title">${esc(data.title || row.querySelector('th a')?.textContent)}</h3>
        <ul class="synth-loupe-comps">${covers.map(c => `<li class="${c === focus ? 'is-focus' : ''}"><span>C${c}</span>${esc(competences[c - 1] || '')}</li>`).join('')}</ul>
        ${data.summary ? `<p class="synth-loupe-summary">${esc(data.summary)}</p>` : ''}
        <p class="synth-loupe-cta">Cliquer sur la ligne pour lire l’article <span aria-hidden="true">→</span></p>
      </div>`;
  };
  const place = row => {
    const host = document.fullscreenElement === section ? section : document.body;
    if (card.parentElement !== host) host.append(card);
    // inside the scaled section (full screen), undo its zoom so the card keeps its real size
    const z = host === section ? parseFloat(section.style.zoom) || 1 : 1;
    card.style.zoom = z !== 1 ? String(1 / z) : '';
    const r = row.getBoundingClientRect();
    const t = table.getBoundingClientRect();
    const w = card.offsetWidth, h = card.offsetHeight, gap = 18;
    // beside the table when there is room; otherwise above or below the row (never over it),
    // on the side of the screen with more space, aligned on the right part of the table
    let left, top;
    if (t.right + gap + w <= window.innerWidth - 12) {
      left = t.right + gap;
      top = r.top + r.height / 2 - h / 2;
    } else if (t.left - gap - w >= 12) {
      left = t.left - gap - w;
      top = r.top + r.height / 2 - h / 2;
    } else {
      left = Math.min(window.innerWidth - w - 12, t.right - w);
      top = r.top > window.innerHeight / 2 ? r.top - gap - h : r.bottom + gap;
    }
    top = Math.min(window.innerHeight - h - 12, Math.max(80, top));
    card.style.left = `${Math.round(left)}px`;
    card.style.top = `${Math.round(top)}px`;
  };
  const show = row => {
    clearTimeout(hideTimer);
    if (shownRow === row && !card.hidden) return;
    shownRow?.classList.remove('is-loupe');
    shownRow = row;
    row.classList.add('is-loupe');
    fill(row);
    card.hidden = false;
    place(row);
    card.classList.remove('is-in');
    void card.offsetWidth;
    card.classList.add('is-in');
  };
  const hide = (delay = 120) => {
    clearTimeout(hideTimer);
    hideTimer = setTimeout(() => {
      card.hidden = true;
      shownRow?.classList.remove('is-loupe');
      shownRow = null;
    }, delay);
  };

  articleRows.forEach(row => {
    row.addEventListener('pointerenter', () => { if (canHover.matches) show(row); });
    row.addEventListener('pointerleave', () => hide());
    row.querySelector('th a')?.addEventListener('focus', () => show(row));
    row.querySelector('th a')?.addEventListener('blur', () => hide(0));
  });
  document.addEventListener('keydown', event => { if (event.key === 'Escape') hide(0); });
  window.addEventListener('wheel', () => { if (shownRow) hide(0); }, { passive: true });
  // the article opens in a dialog: the card goes away
  table.addEventListener('click', () => hide(0));
})();
