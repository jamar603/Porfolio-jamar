// Article reader: a structured, readable layout for each article, and a scroll system built for
// slow, precise reading.
// Layout (rebuilt from what articles.js renders):
//   hero (cover, period · date, title, summary) → fiche (competences in words, technologies)
//   → body: sticky sommaire on the left, numbered parts on the right → previous / next réalisation.
// Scroll:
//   - a rail on the right: drag its handle slowly to move through the article, click the track to jump,
//     one marker per part; while dragging, a label shows the part and the percentage;
//   - ▲ ▼ buttons: click = one screen, press and hold = continuous slow scroll (speeds up a little);
//   - keys: ↑ ↓ (native, short steps), PageUp/PageDown/Space (one screen), Home/End,
//     ← → previous / next réalisation (order of the tableau de synthèse).
(() => {
  const dialog = document.getElementById('articleDialog');
  const surface = dialog?.querySelector('.article-dialog-surface');
  const content = document.getElementById('articleDialogContent');
  if (!dialog || !surface || !content) return;
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const behavior = () => (reduceMotion.matches ? 'auto' : 'smooth');
  const esc = text => String(text ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);

  // réalisations in the order of the tableau de synthèse, with their competences
  const rows = [...document.querySelectorAll('.synth-table tr[data-article]')];
  const order = rows.map(row => ({
    id: row.dataset.article,
    title: row.querySelector('th a')?.textContent.trim() || row.dataset.article,
    covers: row.dataset.covers.split(' ').map(Number),
    period: row.dataset.period.replace(/^Réalisations en cours de /, ''),
  }));
  let competenceNames = [];
  try { competenceNames = JSON.parse(document.getElementById('synthPreviewData')?.textContent || '{}').competences || []; } catch { competenceNames = []; }
  let currentId = null;
  let fromTable = false;

  // remember which article is opened and whether it came from the table
  document.addEventListener('click', event => {
    const trigger = event.target.closest('[data-open-article], [data-article-id]');
    if (!trigger || trigger.closest('#articleDialog')) return;
    currentId = trigger.dataset.openArticle || trigger.dataset.articleId;
    fromTable = Boolean(trigger.closest('.synth-table, .synth-loupe'));
  }, true);

  // ---------- fixed tools (created once) ----------
  const progress = document.createElement('div');
  progress.className = 'reader-progress';
  progress.innerHTML = '<span></span>';
  const progressFill = progress.firstElementChild;

  // compact sommaire for small screens (the side sommaire is used on wide screens)
  const toc = document.createElement('nav');
  toc.className = 'reader-toc';
  toc.setAttribute('aria-label', 'Parties de l’article');

  const rail = document.createElement('div');
  rail.className = 'reader-rail';
  rail.innerHTML = `
    <button type="button" class="reader-rail-btn" data-reader="up" aria-label="Remonter (maintenir pour défiler lentement)" title="Remonter · maintenir pour défiler">▲</button>
    <div class="reader-track" role="scrollbar" aria-orientation="vertical" aria-controls="articleDialogContent" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" tabindex="0">
      <span class="reader-track-fill"></span>
      <span class="reader-thumb"><span class="reader-thumb-grip" aria-hidden="true"></span></span>
      <span class="reader-bubble" aria-hidden="true"></span>
    </div>
    <button type="button" class="reader-rail-btn" data-reader="down" aria-label="Descendre (maintenir pour défiler lentement)" title="Descendre · maintenir pour défiler">▼</button>
    <span class="reader-percent" aria-hidden="true">0 %</span>
    <button type="button" class="reader-rail-btn reader-rail-top" data-reader="top" aria-label="Revenir en haut de l’article" title="Haut de l’article">⤒</button>`;
  const track = rail.querySelector('.reader-track');
  const thumb = rail.querySelector('.reader-thumb');
  const trackFill = rail.querySelector('.reader-track-fill');
  const bubble = rail.querySelector('.reader-bubble');
  const percent = rail.querySelector('.reader-percent');
  dialog.append(progress, rail);
  surface.prepend(toc);

  let parts = [];
  let markers = [];
  const maxScroll = () => Math.max(1, surface.scrollHeight - surface.clientHeight);
  const stickyOffset = () => (toc.offsetParent ? toc.offsetHeight : 0) + 24;
  const offsetOf = el => el.getBoundingClientRect().top - surface.getBoundingClientRect().top + surface.scrollTop - stickyOffset();

  // ---------- layout ----------
  const restructure = () => {
    if (content.querySelector('.reader-hero')) return;
    const cover = content.querySelector(':scope > .article-dialog-cover');
    const meta = content.querySelector(':scope > .article-dialog-meta');
    const title = content.querySelector(':scope > h2');
    const summary = content.querySelector(':scope > .article-dialog-summary');
    const sections = [...content.querySelectorAll(':scope > .article-dialog-section')];
    const tags = content.querySelector(':scope > .project-tags');
    const info = order.find(o => o.id === currentId);

    const hero = document.createElement('header');
    hero.className = 'reader-hero';
    if (cover) hero.append(cover);
    const heroText = document.createElement('div');
    heroText.className = 'reader-hero-text';
    const kicker = document.createElement('p');
    kicker.className = 'reader-kicker';
    const date = meta?.querySelector('time')?.textContent || '';
    kicker.textContent = [info?.period || meta?.querySelector('.article-category')?.textContent, date].filter(Boolean).join(' · ');
    heroText.append(kicker);
    if (title) heroText.append(title);
    if (summary) heroText.append(summary);
    hero.append(heroText);

    // fiche: competences in words + technologies
    const fiche = document.createElement('div');
    fiche.className = 'reader-fiche';
    if (info?.covers.length) {
      fiche.insertAdjacentHTML('beforeend', `<div class="reader-fiche-block"><p class="reader-fiche-k">Compétences du bloc 1</p><ul class="reader-comps">${info.covers.map(c => `<li><span>C${c}</span>${esc(competenceNames[c - 1] || '')}</li>`).join('')}</ul></div>`);
    }
    if (tags) {
      const block = document.createElement('div');
      block.className = 'reader-fiche-block';
      block.innerHTML = '<p class="reader-fiche-k">Technologies et outils</p>';
      block.append(tags);
      fiche.append(block);
    }

    // numbered parts
    sections.forEach((section, i) => {
      const h3 = section.querySelector('h3');
      if (h3 && !h3.querySelector('.reader-num')) h3.insertAdjacentHTML('afterbegin', `<span class="reader-num">${String(i + 1).padStart(2, '0')}</span>`);
      section.classList.add('reader-part');
    });

    const body = document.createElement('div');
    body.className = 'reader-body';
    const side = document.createElement('aside');
    side.className = 'reader-side';
    // reading time: about 200 words a minute
    const words = sections.reduce((n, sec) => n + (sec.textContent.match(/\S+/g) || []).length, 0) + (summary?.textContent.split(/\s+/).length || 0);
    const minutes = Math.max(1, Math.round(words / 200));
    side.innerHTML = `
      <div class="reader-side-head">
        <p class="reader-side-title">Sommaire</p>
        <p class="reader-side-meta">${sections.length} partie${sections.length > 1 ? 's' : ''} · ${minutes} min de lecture</p>
        <span class="reader-side-bar" aria-hidden="true"><span></span></span>
      </div>
      <ol class="reader-side-list">${sections.map((s, i) => `<li><button type="button" data-part="${i + 1}"><span class="reader-num">${String(i + 1).padStart(2, '0')}</span><span class="reader-side-label">${esc(s.querySelector('h3')?.textContent.replace(/^\d{2}/, '').trim())}</span></button></li>`).join('')}</ol>
      <button type="button" class="reader-side-top" data-part="0">⤒ Haut de l’article</button>`;
    const main = document.createElement('div');
    main.className = 'reader-main';
    main.append(...sections);
    body.append(side, main);

    meta?.remove();
    content.replaceChildren(hero, ...(fiche.children.length ? [fiche] : []), body);
  };

  const buildFoot = () => {
    const main = content.querySelector('.reader-main') || content;
    main.querySelector('.reader-foot')?.remove();
    const index = order.findIndex(o => o.id === currentId);
    if (index < 0) return;
    const prev = order[index - 1];
    const next = order[index + 1];
    const foot = document.createElement('div');
    foot.className = 'reader-foot';
    foot.innerHTML = `
      ${prev ? `<button type="button" class="reader-nav reader-prev" data-go="${esc(prev.id)}"><small>← Réalisation précédente</small><span>${esc(prev.title)}</span></button>` : '<span></span>'}
      <button type="button" class="reader-back" data-reader="back">${fromTable ? 'Retour au tableau de synthèse' : 'Fermer l’article'}<small>${index + 1} / ${order.length}</small></button>
      ${next ? `<button type="button" class="reader-nav reader-next" data-go="${esc(next.id)}"><small>Réalisation suivante →</small><span>${esc(next.title)}</span></button>` : '<span></span>'}`;
    main.append(foot);
  };

  let building = false;
  const build = () => {
    building = true;
    restructure();
    buildFoot();
    const heroTitle = content.querySelector('.reader-hero h2') || content.firstElementChild;
    parts = [{ label: 'Présentation', el: heroTitle }, ...[...content.querySelectorAll('.reader-part')].map(s => ({ label: s.querySelector('h3')?.textContent.replace(/^\d{2}/, '').trim() || 'Partie', el: s }))];
    toc.innerHTML = parts.map((p, i) => `<button type="button" data-part="${i}">${i ? `<span class="reader-num">${String(i).padStart(2, '0')}</span>` : ''}${esc(p.label)}</button>`).join('');
    track.querySelectorAll('.reader-marker').forEach(m => m.remove());
    markers = parts.slice(1).map((p, i) => {
      const m = document.createElement('button');
      m.type = 'button';
      m.className = 'reader-marker';
      m.dataset.part = String(i + 1);
      m.title = p.label;
      m.setAttribute('aria-label', `Aller à : ${p.label}`);
      track.append(m);
      return m;
    });
    requestAnimationFrame(() => { building = false; layoutRail(); update(); });
  };

  // ---------- rail geometry ----------
  const thumbSize = () => Math.max(44, track.clientHeight * (surface.clientHeight / surface.scrollHeight));
  const layoutRail = () => {
    const th = thumbSize();
    thumb.style.height = `${th}px`;
    const usable = track.clientHeight - th;
    markers.forEach((m, i) => {
      const ratio = Math.min(1, Math.max(0, offsetOf(parts[i + 1].el) / maxScroll()));
      m.style.top = `${th / 2 + ratio * usable}px`;
    });
  };
  const activePart = () => {
    const line = surface.scrollTop + surface.clientHeight * 0.3;
    let active = 0;
    parts.forEach((p, i) => { if (offsetOf(p.el) <= line) active = i; });
    if (surface.scrollTop >= maxScroll() - 2) active = parts.length - 1;
    return active;
  };
  const update = () => {
    const ratio = Math.min(1, surface.scrollTop / maxScroll());
    progressFill.style.transform = `scaleX(${ratio})`;
    const pct = Math.round(ratio * 100);
    percent.textContent = `${pct} %`;
    track.setAttribute('aria-valuenow', String(pct));
    const th = thumbSize();
    const y = ratio * (track.clientHeight - th);
    thumb.style.transform = `translateY(${y}px)`;
    trackFill.style.height = `${y + th / 2}px`;
    rail.classList.toggle('is-top', surface.scrollTop < 30);
    rail.classList.toggle('is-end', ratio > 0.995);
    const active = activePart();
    bubble.textContent = `${parts[active]?.label || ''} · ${pct} %`;
    bubble.style.transform = `translateY(${y + th / 2}px)`;
    [...toc.querySelectorAll('[data-part]'), ...content.querySelectorAll('.reader-side [data-part]')].forEach(b => {
      const on = Number(b.dataset.part) === active;
      b.classList.toggle('is-active', on);
      if (on) b.setAttribute('aria-current', 'true'); else b.removeAttribute('aria-current');
    });
    markers.forEach((m, i) => m.classList.toggle('is-passed', i + 1 <= active));
    content.querySelectorAll('.reader-side-list [data-part]').forEach(b => b.classList.toggle('is-passed', Number(b.dataset.part) < active));
    const sideBar = content.querySelector('.reader-side-bar span');
    if (sideBar) sideBar.style.transform = `scaleX(${ratio})`;
    const activeChip = toc.querySelector('.is-active');
    if (activeChip && toc.scrollWidth > toc.clientWidth) toc.scrollTo({ left: activeChip.offsetLeft - toc.clientWidth / 3 });
  };

  // ---------- scrolling actions ----------
  const pageStep = () => surface.clientHeight - stickyOffset() - 80;
  const page = dir => surface.scrollBy({ top: dir * pageStep(), behavior: behavior() });
  const goPart = i => {
    const el = parts[i]?.el;
    if (!el) return;
    surface.scrollTo({ top: i === 0 ? 0 : Math.max(0, offsetOf(el)), behavior: behavior() });
  };

  // drag the handle: the article follows the pointer exactly, so a slow drag reads slowly
  let drag = null;
  thumb.addEventListener('pointerdown', event => {
    event.preventDefault();
    thumb.setPointerCapture(event.pointerId);
    drag = { y: event.clientY, start: surface.scrollTop };
    rail.classList.add('is-dragging');
  });
  thumb.addEventListener('pointermove', event => {
    if (!drag) return;
    const usable = Math.max(1, track.clientHeight - thumbSize());
    surface.scrollTop = drag.start + (event.clientY - drag.y) * (maxScroll() / usable);
  });
  const endDrag = () => { drag = null; rail.classList.remove('is-dragging'); };
  thumb.addEventListener('pointerup', endDrag);
  thumb.addEventListener('pointercancel', endDrag);
  // click on the track (outside the handle) jumps there
  track.addEventListener('pointerdown', event => {
    if (event.target.closest('.reader-thumb, .reader-marker')) return;
    const r = track.getBoundingClientRect();
    const th = thumbSize();
    const ratio = Math.min(1, Math.max(0, (event.clientY - r.top - th / 2) / (r.height - th)));
    surface.scrollTo({ top: ratio * maxScroll(), behavior: behavior() });
  });
  track.addEventListener('click', event => {
    const m = event.target.closest('.reader-marker');
    if (m) goPart(Number(m.dataset.part));
  });
  // keyboard on the rail: arrows = small steps, PageUp/PageDown = one screen
  track.addEventListener('keydown', event => {
    const steps = { ArrowDown: 60, ArrowUp: -60, PageDown: pageStep(), PageUp: -pageStep() };
    if (steps[event.key] === undefined) return;
    event.preventDefault();
    event.stopPropagation();
    surface.scrollBy({ top: steps[event.key], behavior: behavior() });
  });

  // ▲ ▼: click = one screen; press and hold = continuous slow scroll, a little faster after a second
  let hold = null;
  const stopHold = () => {
    if (!hold) return;
    cancelAnimationFrame(hold.frame);
    clearTimeout(hold.timer);
    hold = null;
    rail.classList.remove('is-holding');
  };
  rail.querySelectorAll('[data-reader="up"], [data-reader="down"]').forEach(btn => {
    const dir = btn.dataset.reader === 'down' ? 1 : -1;
    btn.addEventListener('pointerdown', event => {
      if (event.button !== 0) return;
      btn.setPointerCapture(event.pointerId);
      hold = { dir, moving: false, frame: 0, timer: 0 };
      hold.timer = setTimeout(() => {
        if (!hold) return;
        hold.moving = true;
        rail.classList.add('is-holding');
        const started = performance.now();
        let last = started;
        const step = now => {
          if (!hold) return;
          const dt = (now - last) / 1000;
          last = now;
          const speed = now - started < 1200 ? 220 : 420; // px per second: slow, then a bit quicker
          surface.scrollTop += hold.dir * speed * dt;
          hold.frame = requestAnimationFrame(step);
        };
        hold.frame = requestAnimationFrame(step);
      }, 260);
    });
    btn.addEventListener('pointerup', () => {
      const wasMoving = hold?.moving;
      stopHold();
      if (!wasMoving) page(dir);
    });
    btn.addEventListener('pointercancel', stopHold);
    btn.addEventListener('keydown', event => {
      if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); page(dir); }
    });
  });
  rail.querySelector('[data-reader="top"]').addEventListener('click', () => surface.scrollTo({ top: 0, behavior: behavior() }));

  // ---------- previous / next réalisation, back to the table ----------
  const openOther = id => {
    if (typeof openArticleById !== 'function') return;
    currentId = id;
    openArticleById(id);
  };
  const backToTable = () => {
    document.getElementById('closeArticleDialog')?.click();
    if (!fromTable) return;
    const row = document.querySelector(`.synth-table tr[data-article="${CSS.escape(currentId || '')}"]`);
    setTimeout(() => {
      document.getElementById('synthese')?.scrollIntoView({ behavior: behavior() });
      if (row) {
        row.classList.add('is-recent');
        setTimeout(() => row.classList.remove('is-recent'), 2200);
        row.querySelector('th a')?.focus({ preventScroll: true });
      }
    }, 220);
  };

  surface.addEventListener('scroll', () => requestAnimationFrame(update), { passive: true });
  window.addEventListener('resize', () => { layoutRail(); update(); });
  dialog.addEventListener('click', event => {
    const partBtn = event.target.closest('.reader-toc [data-part], .reader-side [data-part]');
    if (partBtn) { goPart(Number(partBtn.dataset.part)); return; }
    const go = event.target.closest('[data-go]');
    if (go) { openOther(go.dataset.go); return; }
    if (event.target.closest('[data-reader="back"]')) backToTable();
  });

  dialog.addEventListener('keydown', event => {
    if (event.altKey || event.ctrlKey || event.metaKey) return;
    if (event.target.closest('input, textarea, select')) return;
    if (document.querySelector('.photo-lightbox[open]')) return;
    const index = order.findIndex(o => o.id === currentId);
    const keys = {
      ArrowRight: () => order[index + 1] && openOther(order[index + 1].id),
      ArrowLeft: () => order[index - 1] && openOther(order[index - 1].id),
      PageDown: () => page(1),
      PageUp: () => page(-1),
      Home: () => surface.scrollTo({ top: 0, behavior: behavior() }),
      End: () => surface.scrollTo({ top: maxScroll(), behavior: behavior() }),
    };
    // Space scrolls a screen unless a button or link has the focus (Space then presses it)
    if (event.key === ' ' && !event.target.closest('button, a, [role="scrollbar"]')) keys[' '] = () => page(event.shiftKey ? -1 : 1);
    if (keys[event.key]) { event.preventDefault(); keys[event.key](); }
  });

  // rebuild each time articles.js replaces the content (opening, previous / next)
  let pending = 0;
  new MutationObserver(() => {
    if (building) return;
    cancelAnimationFrame(pending);
    pending = requestAnimationFrame(build);
  }).observe(content, { childList: true });
  new MutationObserver(() => {
    if (!dialog.open) stopHold();
    else requestAnimationFrame(() => { layoutRail(); update(); });
  }).observe(dialog, { attributes: true, attributeFilter: ['open'] });
  // images change the article height: keep the rail markers in place
  content.addEventListener('load', () => { layoutRail(); update(); }, true);
})();
