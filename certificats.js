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
    cards.forEach((card, i) => card.classList.toggle('is-active', i === index));
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
  const start = () => {
    if (reducedMotion.matches || timer) return;
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
})();
