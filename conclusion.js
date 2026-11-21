// Conclusion section motion: words, stats, path and cards play in sequence each time the section
// enters the screen (so it replays during the presentation), numbers count up, cards follow the pointer.
// Without JavaScript or with reduced motion everything is shown as is.
(() => {
  const section = document.getElementById('conclusion');
  if (!section || !('IntersectionObserver' in window)) return;
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (reducedMotion.matches) return;

  section.classList.add('conclu-anim');
  const counters = [...section.querySelectorAll('[data-conclu-count]')];
  let frames = [];

  const countUp = () => {
    frames.forEach(cancelAnimationFrame);
    frames = [];
    counters.forEach((el, i) => {
      const target = Number(el.dataset.concluCount);
      const delay = 450 + i * 140;
      const start = performance.now() + delay;
      el.textContent = '0';
      const tick = now => {
        const t = Math.max(0, Math.min(1, (now - start) / 1100));
        el.textContent = String(Math.round(target * (1 - (1 - t) ** 4)));
        if (t < 1) frames[i] = requestAnimationFrame(tick);
      };
      frames[i] = requestAnimationFrame(tick);
    });
  };

  new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting && entry.intersectionRatio >= 0.2) {
        if (!section.classList.contains('is-in')) {
          section.classList.add('is-in');
          countUp();
        }
      } else if (!entry.isIntersecting) {
        // fully off screen: reset so the sequence plays again next time
        section.classList.remove('is-in');
      }
    });
  }, { threshold: [0, 0.2] }).observe(section);

  // presentation mode: replay the whole sequence when the conclusion becomes the current step
  section.addEventListener('present:enter', () => {
    section.classList.remove('is-in');
    void section.offsetWidth;
    section.classList.add('is-in');
    countUp();
  });

  // spotlight that follows the pointer on each card
  if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
    section.querySelectorAll('.conclu-card').forEach(card => {
      card.addEventListener('pointermove', event => {
        const rect = card.getBoundingClientRect();
        const x = (event.clientX - rect.left) / rect.width;
        const y = (event.clientY - rect.top) / rect.height;
        card.style.setProperty('--mx', `${(x * 100).toFixed(1)}%`);
        card.style.setProperty('--my', `${(y * 100).toFixed(1)}%`);
        card.style.setProperty('--rx', `${((0.5 - y) * 6).toFixed(2)}deg`);
        card.style.setProperty('--ry', `${((x - 0.5) * 8).toFixed(2)}deg`);
      });
      card.addEventListener('pointerleave', () => {
        card.style.setProperty('--rx', '0deg');
        card.style.setProperty('--ry', '0deg');
      });
    });
  }
})();
