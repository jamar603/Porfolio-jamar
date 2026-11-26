// certificate slideshow: arrows, dots, active-slide highlight and gentle autoplay
(() => {
  const slider = document.querySelector('.cert-slider');
  if (!slider) return;

  const track = slider.querySelector('.cert-grid');
  const cards = [...track.querySelectorAll('.cert-card')];
  const dotsBox = slider.querySelector('.cert-dots');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const AUTOPLAY_DELAY = 4000;
  let current = 0;
  let timer = null;
  // ignore scroll events caused by goTo(), so edge cards that cannot be centered keep their highlight
  let programmaticUntil = 0;

  const dots = cards.map((card, index) => {
    const dot = document.createElement('button');
    dot.type = 'button';
    dot.className = 'cert-dot';
    dot.setAttribute('role', 'tab');
    dot.setAttribute('aria-label', `Certificat ${index + 1} sur ${cards.length}`);
    dot.addEventListener('click', () => goTo(index));
    dotsBox.append(dot);
    return dot;
  });

  const setActive = (index) => {
    current = index;
    cards.forEach((card, i) => {
      card.classList.toggle('is-active', i === index);
      card.classList.toggle('is-before', i < index);
      card.classList.toggle('is-after', i > index);
    });
    dots.forEach((dot, i) => dot.setAttribute('aria-selected', String(i === index)));
  };

  const goTo = (index) => {
    const target = cards[(index + cards.length) % cards.length];
    // center the card inside the track without scrolling the whole page
    const left = target.offsetLeft - (track.clientWidth - target.offsetWidth) / 2;
    programmaticUntil = performance.now() + 900;
    track.scrollTo({ left, behavior: reducedMotion.matches ? 'auto' : 'smooth' });
    setActive(cards.indexOf(target));
  };

  // keep the highlighted card in sync when the visitor swipes or scrolls the track
  let scrollFrame = 0;
  track.addEventListener('scroll', () => {
    if (performance.now() < programmaticUntil) return;
    cancelAnimationFrame(scrollFrame);
    scrollFrame = requestAnimationFrame(() => {
      const center = track.scrollLeft + track.clientWidth / 2;
      let closest = 0;
      let best = Infinity;
      cards.forEach((card, i) => {
        const distance = Math.abs(card.offsetLeft + card.offsetWidth / 2 - center);
        if (distance < best) { best = distance; closest = i; }
      });
      if (closest !== current) setActive(closest);
    });
  }, { passive: true });

  slider.querySelectorAll('.cert-nav').forEach((button) => {
    button.addEventListener('click', () => {
      goTo(current + Number(button.dataset.dir));
      restart();
    });
  });

  track.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowRight') { event.preventDefault(); goTo(current + 1); }
    if (event.key === 'ArrowLeft') { event.preventDefault(); goTo(current - 1); }
  });

  const stop = () => { clearInterval(timer); timer = null; };
  // on a computer the certificates are shown as a wall (grid, all visible): no autoplay there
  const isWall = () => getComputedStyle(track).display === 'grid';
  const start = () => {
    if (reducedMotion.matches || timer || isWall()) return;
    timer = setInterval(() => goTo(current + 1), AUTOPLAY_DELAY);
  };
  const restart = () => { stop(); start(); };

  slider.addEventListener('pointerenter', stop);
  slider.addEventListener('pointerleave', start);
  slider.addEventListener('focusin', stop);
  slider.addEventListener('focusout', start);

  // only autoplay while the slideshow is on screen
  new IntersectionObserver(([entry]) => {
    if (entry.isIntersecting) start(); else stop();
  }, { threshold: 0.3 }).observe(slider);

  setActive(0);

  // certificate viewer: the whole document, full screen, with previous / next,
  // a strip of thumbnails to jump to any other certificate, keyboard and swipe
  const viewer = document.createElement('dialog');
  viewer.className = 'cert-viewer';
  viewer.setAttribute('aria-label', 'Certificat agrandi');
  viewer.innerHTML = `
    <header class="cert-viewer-bar">
      <div class="cert-viewer-text">
        <span class="cert-viewer-badge"></span>
        <strong class="cert-viewer-title"></strong>
        <span class="cert-viewer-meta"></span>
      </div>
      <span class="cert-viewer-count" aria-live="polite"></span>
      <a class="cert-viewer-open" target="_blank" rel="noopener">Ouvrir l’original</a>
      <button type="button" class="cert-viewer-close" aria-label="Fermer">×</button>
    </header>
    <div class="cert-viewer-body">
      <div class="cert-viewer-stage">
        <button type="button" class="cert-viewer-arrow" data-dir="-1" aria-label="Certificat précédent">‹</button>
        <img class="cert-viewer-img" alt="">
        <button type="button" class="cert-viewer-arrow" data-dir="1" aria-label="Certificat suivant">›</button>
      </div>
      <aside class="cert-viewer-panel" aria-label="Explication du certificat"></aside>
    </div>
    <nav class="cert-viewer-strip" aria-label="Tous les certificats"></nav>`;
  document.body.append(viewer);

  const viewerImg = viewer.querySelector('.cert-viewer-img');
  const viewerBadge = viewer.querySelector('.cert-viewer-badge');
  const viewerTitle = viewer.querySelector('.cert-viewer-title');
  const viewerMeta = viewer.querySelector('.cert-viewer-meta');
  const viewerCount = viewer.querySelector('.cert-viewer-count');
  const viewerOpen = viewer.querySelector('.cert-viewer-open');
  const strip = viewer.querySelector('.cert-viewer-strip');
  const panel = viewer.querySelector('.cert-viewer-panel');
  const body = viewer.querySelector('.cert-viewer-body');
  let viewerIndex = 0;

  // every attestation (the CNIL card groups 5 of them): written by index.php in .cert-docs
  const docs = [...document.querySelectorAll('.cert-docs template')].map(tpl => ({
    src: tpl.dataset.src,
    alt: tpl.dataset.alt || '',
    title: tpl.dataset.title || '',
    badge: tpl.dataset.badge || '',
    meta: '',
    details: tpl,
  }));
  // card shown for a document: the last card whose first document comes at or before it
  const cardOf = index => cards.reduce((found, card, i) => (Number(card.dataset.doc) <= index ? i : found), 0);

  const thumbs = docs.map((doc, index) => {
    const thumb = document.createElement('button');
    thumb.type = 'button';
    thumb.className = 'cert-viewer-thumb';
    thumb.setAttribute('aria-label', `${doc.badge} · ${doc.title}`);
    thumb.innerHTML = `<img src="${doc.src}" alt="" loading="lazy">`;
    thumb.addEventListener('click', () => show(index));
    strip.append(thumb);
    return thumb;
  });

  // warm the cache so previous / next appear without delay
  const preload = index => { const img = new Image(); img.src = docs[(index + docs.length) % docs.length].src; };

  function show(index){
    viewerIndex = (index + docs.length) % docs.length;
    const doc = docs[viewerIndex];
    viewerImg.classList.remove('is-in');
    viewerImg.src = doc.src;
    viewerImg.alt = doc.alt;
    viewerBadge.textContent = doc.badge;
    viewerTitle.textContent = doc.title;
    viewerMeta.textContent = doc.meta;
    viewerCount.textContent = `${viewerIndex + 1} / ${docs.length}`;
    viewerOpen.href = doc.src;
    // explanation of this certificate (template written in index.php)
    panel.replaceChildren(...(doc.details ? [doc.details.content.cloneNode(true)] : []));
    panel.scrollTop = 0;
    body.scrollTop = 0;
    thumbs.forEach((thumb, i) => thumb.setAttribute('aria-current', String(i === viewerIndex)));
    thumbs[viewerIndex].scrollIntoView({ block: 'nearest', inline: 'center', behavior: reducedMotion.matches ? 'auto' : 'smooth' });
    preload(viewerIndex + 1);
    preload(viewerIndex - 1);
  }
  const reveal = () => requestAnimationFrame(() => viewerImg.classList.add('is-in'));
  viewerImg.addEventListener('load', reveal);

  function openViewer(index){
    stop();
    show(index);
    viewer.showModal();
    viewer.querySelector('.cert-viewer-close').focus({ preventScroll: true });
  }

  viewer.querySelector('.cert-viewer-close').addEventListener('click', () => viewer.close());
  viewer.querySelectorAll('.cert-viewer-arrow').forEach(button => {
    button.addEventListener('click', () => show(viewerIndex + Number(button.dataset.dir)));
  });
  // click on the dark background closes; click on the document itself does nothing
  viewer.addEventListener('click', event => {
    if (event.target === viewer || event.target.classList.contains('cert-viewer-stage') || event.target.classList.contains('cert-viewer-body')) viewer.close();
  });
  viewer.addEventListener('keydown', event => {
    if (event.key === 'ArrowRight' || event.key === 'PageDown') { event.preventDefault(); show(viewerIndex + 1); }
    if (event.key === 'ArrowLeft' || event.key === 'PageUp') { event.preventDefault(); show(viewerIndex - 1); }
    if (event.key === 'Home') { event.preventDefault(); show(0); }
    if (event.key === 'End') { event.preventDefault(); show(docs.length - 1); }
  });
  let touchX = null;
  viewer.addEventListener('touchstart', event => { touchX = event.touches[0].clientX; }, { passive: true });
  viewer.addEventListener('touchend', event => {
    if (touchX === null) return;
    const distance = event.changedTouches[0].clientX - touchX;
    touchX = null;
    if (Math.abs(distance) > 50) show(viewerIndex + (distance < 0 ? 1 : -1));
  }, { passive: true });
  // back in the page: the slideshow follows the last certificate seen
  viewer.addEventListener('close', () => {
    if (!isWall()) goTo(cardOf(viewerIndex));
    cards[cardOf(viewerIndex)].focus({ preventScroll: true });
  });

  cards.forEach(card => {
    card.addEventListener('click', event => {
      if (event.ctrlKey || event.metaKey || event.shiftKey || event.button === 1) return;
      event.preventDefault();
      openViewer(Number(card.dataset.doc) || 0);
    });
  });
})();
