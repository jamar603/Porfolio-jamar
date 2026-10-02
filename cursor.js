// animated cursor: a neon dot that follows the pointer + a ring that trails behind it
(() => {
  const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (!finePointer.matches || reducedMotion.matches) return;

  const dot = document.createElement('div');
  const ring = document.createElement('div');
  dot.className = 'cursor-dot';
  ring.className = 'cursor-ring';
  dot.setAttribute('aria-hidden', 'true');
  ring.setAttribute('aria-hidden', 'true');
  document.body.append(ring, dot);
  document.documentElement.classList.add('has-custom-cursor');

  const interactive = 'a, button, summary, label, [role="button"], .hero-art';
  const textField = 'input, textarea, select, [contenteditable="true"]';
  const pointer = { x: window.innerWidth / 2, y: window.innerHeight / 2 };
  const trail = { x: pointer.x, y: pointer.y };
  let visible = false;

  window.addEventListener('pointermove', (event) => {
    pointer.x = event.clientX;
    pointer.y = event.clientY;
    dot.style.transform = `translate3d(${pointer.x}px, ${pointer.y}px, 0)`;
    if (!visible) {
      visible = true;
      trail.x = pointer.x;
      trail.y = pointer.y;
      document.documentElement.classList.add('cursor-visible');
    }

    const target = event.target instanceof Element ? event.target : null;
    const overText = !!target?.closest(textField);
    document.documentElement.classList.toggle('cursor-hover', !overText && !!target?.closest(interactive));
    document.documentElement.classList.toggle('cursor-text', overText);
  }, { passive: true });

  document.addEventListener('pointerleave', () => {
    visible = false;
    document.documentElement.classList.remove('cursor-visible');
  });
  window.addEventListener('pointerdown', () => document.documentElement.classList.add('cursor-down'));
  window.addEventListener('pointerup', () => document.documentElement.classList.remove('cursor-down'));

  const tick = () => {
    // ease the ring toward the pointer so it lags slightly behind
    trail.x += (pointer.x - trail.x) * 0.18;
    trail.y += (pointer.y - trail.y) * 0.18;
    ring.style.transform = `translate3d(${trail.x}px, ${trail.y}px, 0)`;
    requestAnimationFrame(tick);
  };
  requestAnimationFrame(tick);
})();
