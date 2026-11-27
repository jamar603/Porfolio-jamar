// Presentation mode for the E5 oral: a section rail, a control bar with a 10-minute timer,
// and keyboard / clicker navigation (PageDown, PageUp, arrows left and right, Home, End).
(() => {
  // only the sections used for the E5 oral, in page order; the others stay on the page when scrolling
  const e5Sections = ['accueil', 'epreuve-e5', 'criteres-e5', 'profil', 'formation', 'experience', 'projets', 'synthese', 'articles', 'atouts', 'veille', 'conclusion'];
  const steps = [...document.querySelectorAll('body > section[id]')].filter(s => e5Sections.includes(s.id));
  if (!steps.length) return;
  // presentation mode hides every other section (motivation, compétences, contact…) until it ends
  document.querySelectorAll('body > section').forEach(s => { if (!steps.includes(s)) s.classList.add('present-skip'); });

  const labelOf = section => section.dataset.stepLabel
    || section.querySelector('.section-head h2')?.textContent.trim()
    || section.id;
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const navHeight = () => document.querySelector('body > nav')?.offsetHeight || 0;

  // section rail
  // a div, not a <nav>: the stylesheet styles every nav element as the site header
  const rail = document.createElement('div');
  rail.className = 'present-rail';
  rail.setAttribute('role', 'navigation');
  rail.setAttribute('aria-label', 'Sommaire des sections');
  const escapeHtml = text => text.replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);
  rail.innerHTML = '<ol>' + steps.map((s, i) =>
    `<li><a href="#${s.id}" data-step="${i}"><span class="present-rail-label">${escapeHtml(labelOf(s))}</span><span class="present-rail-dot" aria-hidden="true"></span></a></li>`
  ).join('') + '</ol>';
  document.body.append(rail);
  const railLinks = [...rail.querySelectorAll('a')];
  rail.addEventListener('click', event => {
    const link = event.target.closest('a[data-step]');
    if (!link) return;
    event.preventDefault();
    goTo(Number(link.dataset.step));
  });

  // start button and control bar
  const launcher = document.createElement('button');
  launcher.type = 'button';
  launcher.className = 'present-launch';
  launcher.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg><span>Mode présentation</span>';
  launcher.setAttribute('aria-keyshortcuts', 'P');
  document.body.append(launcher);

  const bar = document.createElement('div');
  bar.className = 'present-bar';
  bar.setAttribute('role', 'toolbar');
  bar.setAttribute('aria-label', 'Contrôles de présentation');
  bar.hidden = true;
  bar.innerHTML = `
    <button type="button" class="pb-btn" data-act="prev" aria-label="Section précédente" aria-keyshortcuts="PageUp ArrowLeft">‹</button>
    <p class="pb-step" aria-live="polite"><span class="pb-count"></span><span class="pb-label"></span></p>
    <button type="button" class="pb-btn" data-act="next" aria-label="Section suivante" aria-keyshortcuts="PageDown ArrowRight">›</button>
    <button type="button" class="pb-timer" data-act="timer" aria-label="Chronomètre de la phase 1, démarrer ou mettre en pause"><span class="pb-time">10:00</span><span class="pb-timer-hint">Phase 1</span></button>
    <button type="button" class="pb-btn pb-small" data-act="reset" aria-label="Remettre le chronomètre à 10 minutes" title="Remettre à zéro">↺</button>
    <button type="button" class="pb-btn pb-small" data-act="full" aria-label="Plein écran" aria-keyshortcuts="F" title="Plein écran (F)">⛶</button>
    <button type="button" class="pb-btn pb-small pb-exit" data-act="exit" aria-label="Quitter le mode présentation" title="Quitter (Échap)">×</button>`;
  document.body.append(bar);
  const countEl = bar.querySelector('.pb-count');
  const labelEl = bar.querySelector('.pb-label');
  const timerBtn = bar.querySelector('.pb-timer');
  const timeEl = bar.querySelector('.pb-time');

  // Every section is scaled (CSS zoom) to fit the screen under the header: on a computer while browsing
  // (so a section reached from the menu is seen whole) and always in presentation mode (above the
  // control bar). A section still too tall at the minimum scale stays scrollable; in presentation mode
  // it is split into "pages" of one screen each, so every step shows a complete screenful.
  // Navigation scroll, animated by hand: the browser's smooth scroll stalls while the WebGL background
  // and the 3D scene are busy, and could stop short of the section. This one lasts a fixed time whatever
  // the frame rate, reads the target again on every frame (images loading above it cannot shift it)
  // and ends exactly on it. A wheel, touch or key from the visitor stops it.
  let glide = 0;
  const stopGlide = () => { cancelAnimationFrame(glide); glide = 0; };
  ['wheel', 'touchstart', 'keydown'].forEach(type => window.addEventListener(type, event => {
    // keys handled by the presentation (next / previous) start a new glide themselves
    if (type === 'keydown' && (event.defaultPrevented || !['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End', ' '].includes(event.key))) return;
    stopGlide();
  }, { passive: true }));
  const glideTo = getTarget => {
    stopGlide();
    const clamp = y => Math.max(0, Math.min(document.documentElement.scrollHeight - window.innerHeight, y));
    if (reduceMotion.matches) { window.scrollTo({ top: clamp(getTarget()), behavior: 'instant' }); return; }
    const startY = window.scrollY;
    const distance = Math.abs(clamp(getTarget()) - startY);
    const duration = Math.min(900, 380 + distance * 0.12);
    const t0 = performance.now();
    const ease = t => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);
    const step = now => {
      const t = Math.min(1, (now - t0) / duration);
      const target = clamp(getTarget());
      window.scrollTo({ top: startY + (target - startY) * ease(t), behavior: 'instant' });
      if (t < 1) glide = requestAnimationFrame(step);
      else {
        glide = 0;
        // final check once late layout (images, fonts) has settled
        setTimeout(() => {
          if (glide) return;
          const final = clamp(getTarget());
          if (Math.abs(window.scrollY - final) > 2) window.scrollTo({ top: final, behavior: 'instant' });
        }, 180);
      }
    };
    glide = requestAnimationFrame(step);
  };

  // Browsing: every section is shrunk until it fits the screen whole (floor 0.5 for tiny screens).
  // Presentation (read by the jury): never below 0.85 (a taller section is split into pages instead),
  // and a short section is enlarged up to 130 % to fill the screen, as wide as the screen allows.
  const MIN_ZOOM = 0.5;
  const MIN_ZOOM_PRESENTING = 0.85;
  const MAX_ZOOM_PRESENTING = 1.3;
  const ONE_PAGE_ZOOM = 0.72;
  const allSections = [...document.querySelectorAll('body > section')];
  // computers only (mouse or trackpad): touch tablets keep the natural, fluid page scroll with full-size text
  const desktop = window.matchMedia('(min-width: 961px) and (min-height: 560px) and (hover: hover) and (pointer: fine)');
  let pages = steps.map(() => 1);
  const fitting = () => presenting() || desktop.matches;
  const available = () => window.innerHeight - navHeight() - (bar.hidden ? 8 : bar.offsetHeight + 30);
  const sectionTop = section => section === allSections[0] ? 0 : section.getBoundingClientRect().top + window.scrollY - navHeight();
  const fitSections = () => {
    allSections.forEach(s => { s.style.zoom = ''; s.style.minHeight = ''; });
    document.documentElement.classList.toggle('fit-sections', fitting());
    pages = steps.map(() => 1);
    if (!fitting()) return;
    const room = available();
    const on = presenting();
    // natural heights first, then every zoom at once (one zoom must not change the next measure)
    const zoomOf = new Map(allSections.map(s => {
      const h = s.getBoundingClientRect().height;
      let z = 1;
      // presenting: a section that fits at 72 % or more stays on one page (no split for a small overflow);
      // a really long one keeps 85 % and is split into pages
      // browsing: the tableau de synthèse fits the screen but never below 75 % (it stays readable)
      if (h > room) z = on ? (room / h >= ONE_PAGE_ZOOM ? room / h : MIN_ZOOM_PRESENTING) : Math.max(s.id === 'synthese' ? 0.75 : MIN_ZOOM, room / h);
      else if (on) {
        const width = s.querySelector('.container')?.offsetWidth || s.offsetWidth;
        z = Math.max(1, Math.min(MAX_ZOOM_PRESENTING, room / h, (window.innerWidth - 170) / width));
      }
      return [s, z];
    }));
    zoomOf.forEach((z, s) => {
      if (z !== 1) s.style.zoom = z.toFixed(3);
      // every section is at least one screen high (in screen pixels, whatever its zoom):
      // the next section never shows below a short one
      s.style.minHeight = `${(room / z).toFixed(1)}px`;
    });
    if (on) pages = steps.map(s => Math.max(1, Math.ceil((s.getBoundingClientRect().height - 8) / room)));
  };
  desktop.addEventListener('change', () => { fitSections(); render(); });

  // in-page links (menu, buttons) land exactly on the top of the section, under the header
  document.addEventListener('click', event => {
    const link = event.target.closest('a[href^="#"]');
    if (!link || event.defaultPrevented || link.closest('.present-rail') || !fitting()) return;
    const target = document.getElementById(link.getAttribute('href').slice(1));
    if (!target || target.parentElement !== document.body || target.tagName !== 'SECTION') return;
    event.preventDefault();
    const index = steps.indexOf(target);
    if (index >= 0) { goTo(index); return; }
    target.querySelectorAll('.reveal').forEach(el => el.classList.add('is-visible'));
    glideTo(() => sectionTop(target));
    history.replaceState(null, '', `#${target.id}`);
  });
  // where page p of section i starts; the last page ends exactly where the section ends
  const pageStart = (i, p) => {
    const top = sectionTop(steps[i]);
    if (p === 0) return top;
    const end = top + steps[i].getBoundingClientRect().height + navHeight() - window.innerHeight + (bar.hidden ? 0 : bar.offsetHeight + 30);
    return Math.min(top + p * available(), Math.max(top, end));
  };

  // current step = section whose top passed 35% of the viewport, then the page inside it
  let current = 0;
  let currentPage = 0;
  const findCurrent = () => {
    if (presenting()) {
      // the step whose start position is the last one at or above the current scroll position
      const y = window.scrollY + 12;
      let found = [0, 0];
      steps.forEach((s, i) => {
        for (let p = 0; p < pages[i]; p++) if (pageStart(i, p) <= y) found = [i, p];
      });
      return found;
    }
    const line = window.innerHeight * 0.35;
    let index = 0;
    steps.forEach((s, i) => { if (s.getBoundingClientRect().top - line <= 0) index = i; });
    return [index, 0];
  };
  // while a navigation scroll is running, the step it goes to stays the current one,
  // so quick presses on a clicker never repeat a step
  let lockedUntil = 0;
  let rendered = '';
  const render = () => {
    if (performance.now() > lockedUntil) [current, currentPage] = findCurrent();
    const key = `${presenting()}|${current}|${currentPage}|${pages[current]}`;
    if (key === rendered) return;
    rendered = key;
    steps.forEach((section, i) => section.classList.toggle('is-current', presenting() && i === current));
    railLinks.forEach((a, i) => {
      if (i === current) a.setAttribute('aria-current', 'true');
      else a.removeAttribute('aria-current');
    });
    countEl.textContent = `${current + 1} / ${steps.length}`;
    labelEl.textContent = labelOf(steps[current]) + (pages[current] > 1 ? ` · ${currentPage + 1}/${pages[current]}` : '');
  };
  let ticking = false;
  window.addEventListener('scroll', () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => { render(); ticking = false; });
  }, { passive: true });
  window.addEventListener('resize', () => { fitSections(); render(); });
  window.addEventListener('scrollend', () => { if (glide) return; lockedUntil = 0; render(); });

  // shows the whole section at once (its scroll reveals included) with a short entrance
  const enter = section => {
    section.querySelectorAll('.reveal').forEach(el => el.classList.add('is-visible'));
    section.dispatchEvent(new CustomEvent('present:enter'));
    if (reduceMotion.matches) return;
    section.classList.remove('present-enter');
    void section.offsetWidth;
    section.classList.add('present-enter');
  };
  const goTo = (index, page = 0) => {
    const i = Math.max(0, Math.min(steps.length - 1, index));
    const target = steps[i];
    const p = Math.max(0, Math.min(pages[i] - 1, page));
    if (p === 0) enter(target);
    current = i;
    currentPage = p;
    lockedUntil = performance.now() + 1000;
    render();
    glideTo(() => pageStart(i, p));
    history.replaceState(null, '', `#${target.id}`);
  };
  // next / previous: page by page inside a split section, then section by section
  const next = () => {
    if (currentPage < pages[current] - 1) goTo(current, currentPage + 1);
    else if (current < steps.length - 1) goTo(current + 1, 0);
  };
  const prev = () => {
    if (currentPage > 0) goTo(current, currentPage - 1);
    else if (current > 0) goTo(current - 1, pages[current - 1] - 1);
  };

  // 10-minute countdown for phase 1
  const PHASE_SECONDS = 600;
  let remaining = PHASE_SECONDS;
  let timerId = null;
  const showTime = () => {
    const abs = Math.abs(remaining);
    timeEl.textContent = `${remaining < 0 ? '+' : ''}${String(Math.floor(abs / 60)).padStart(2, '0')}:${String(abs % 60).padStart(2, '0')}`;
    timerBtn.dataset.state = remaining <= 0 ? 'over' : remaining <= 60 ? 'warn' : timerId ? 'run' : 'idle';
  };
  const toggleTimer = () => {
    if (timerId) { clearInterval(timerId); timerId = null; }
    else timerId = setInterval(() => { remaining -= 1; showTime(); }, 1000);
    showTime();
  };
  const resetTimer = () => {
    clearInterval(timerId);
    timerId = null;
    remaining = PHASE_SECONDS;
    showTime();
  };

  const presenting = () => document.documentElement.classList.contains('is-presenting');
  const setPresenting = on => {
    const keep = steps[current];
    document.documentElement.classList.toggle('is-presenting', on);
    bar.hidden = !on;
    launcher.hidden = on;
    fitSections();
    if (on) {
      goTo(steps.indexOf(keep));
      render();
      bar.querySelector('[data-act="next"]').focus({ preventScroll: true });
    } else {
      resetTimer();
      if (document.fullscreenElement) document.exitFullscreen?.();
      launcher.focus({ preventScroll: true });
    }
  };
  const toggleFullscreen = () => {
    if (document.fullscreenElement) document.exitFullscreen?.();
    else document.documentElement.requestFullscreen?.().catch(() => {});
  };

  launcher.addEventListener('click', () => setPresenting(true));
  document.querySelectorAll('[data-present-start]').forEach(btn => btn.addEventListener('click', () => {
    setPresenting(true);
    goTo(steps.findIndex(s => s.id === 'profil'));
    setTimeout(render, 900);
  }));
  bar.addEventListener('click', event => {
    const act = event.target.closest('[data-act]')?.dataset.act;
    if (act === 'prev') prev();
    else if (act === 'next') next();
    else if (act === 'timer') toggleTimer();
    else if (act === 'reset') resetTimer();
    else if (act === 'full') toggleFullscreen();
    else if (act === 'exit') setPresenting(false);
  });

  document.addEventListener('keydown', event => {
    if (event.altKey || event.ctrlKey || event.metaKey || event.defaultPrevented) return;
    if (event.target.closest('input, textarea, select, [contenteditable="true"]')) return;
    if (document.querySelector('dialog[open]')) return;

    if (!presenting()) {
      if (event.key === 'p' || event.key === 'P') { event.preventDefault(); setPresenting(true); }
      return;
    }
    const keys = {
      PageDown: next, ArrowRight: next,
      PageUp: prev, ArrowLeft: prev,
      Home: () => goTo(0), End: () => goTo(steps.length - 1),
      f: toggleFullscreen, F: toggleFullscreen,
      t: toggleTimer, T: toggleTimer,
      Escape: () => { if (!document.fullscreenElement) setPresenting(false); },
    };
    // arrows inside scrollable widgets (article rail, certificates, table) keep their own behaviour
    if (event.key.startsWith('Arrow') && event.target.closest('.article-rail, .cert-grid, .synth-wrap')) return;
    if (keys[event.key]) { event.preventDefault(); keys[event.key](); }
  });

  showTime();
  render();
  if (location.hash === '#presentation') setPresenting(true);
  fitSections();
  render();
  // images and fonts change section heights: fit again once everything has loaded
  window.addEventListener('load', () => { fitSections(); render(); });
  document.fonts?.ready.then(() => { fitSections(); render(); });
})();
