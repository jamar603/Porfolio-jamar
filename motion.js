// lively details: hero name cascade, magnetic main button, celebration when the contact form is sent
(() => {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');

  // hero name: each letter rises in turn; screen readers keep the plain word
  let charIndex = 0;
  document.querySelectorAll('.hero h1 .neon-name').forEach((name) => {
    const word = name.textContent;
    name.textContent = '';
    const readable = document.createElement('span');
    readable.className = 'sr-only';
    readable.textContent = word;
    const letters = document.createElement('span');
    letters.className = 'hero-chars';
    letters.setAttribute('aria-hidden', 'true');
    [...word].forEach((char) => {
      const span = document.createElement('span');
      span.className = 'hero-char';
      span.textContent = char;
      span.style.setProperty('--ci', charIndex++);
      letters.append(span);
    });
    name.append(readable, letters);
  });

  // magnetic pull on the main hero button only (one focal element per screen)
  const magnet = document.querySelector('.hero .btn-primary');
  if (magnet && finePointer.matches && !reducedMotion.matches) {
    const STRENGTH = 0.3;
    const SMOOTHING = 0.18;
    const RANGE = 90;
    let x = 0, y = 0, tx = 0, ty = 0, frame = 0;

    const loop = () => {
      x += (tx - x) * SMOOTHING;
      y += (ty - y) * SMOOTHING;
      // `translate` stacks with the CSS hover/press `transform`
      magnet.style.translate = `${x.toFixed(2)}px ${y.toFixed(2)}px`;
      if (Math.abs(tx - x) > 0.05 || Math.abs(ty - y) > 0.05) frame = requestAnimationFrame(loop);
      else frame = 0;
    };
    const kick = () => { if (!frame) frame = requestAnimationFrame(loop); };

    document.addEventListener('pointermove', (event) => {
      const rect = magnet.getBoundingClientRect();
      const dx = event.clientX - (rect.left + rect.width / 2);
      const dy = event.clientY - (rect.top + rect.height / 2);
      const near = Math.abs(dx) < rect.width / 2 + RANGE && Math.abs(dy) < rect.height / 2 + RANGE;
      tx = near ? dx * STRENGTH : 0;
      ty = near ? dy * STRENGTH : 0;
      kick();
    }, { passive: true });
  }

  // contact form sent: a short burst of particles from the send button (rare event, delight allowed)
  const status = document.getElementById('contactStatus');
  const submit = document.querySelector('.contact-submit');
  if (status && submit && !reducedMotion.matches) {
    const colors = ['var(--indigo)', 'var(--mint)', 'var(--coral)', 'var(--amber)'];
    const burst = () => {
      const rect = submit.getBoundingClientRect();
      for (let i = 0; i < 16; i++) {
        const dot = document.createElement('span');
        dot.className = 'burst-dot';
        dot.style.left = `${rect.left + rect.width / 2}px`;
        dot.style.top = `${rect.top + rect.height / 2}px`;
        dot.style.background = colors[i % colors.length];
        document.body.append(dot);
        const angle = (i / 16) * Math.PI * 2 + Math.random() * 0.4;
        const distance = 46 + Math.random() * 40;
        dot.animate([
          { transform: 'translate(-50%, -50%) scale(1)', opacity: 1 },
          { transform: `translate(calc(-50% + ${Math.cos(angle) * distance}px), calc(-50% + ${Math.sin(angle) * distance}px)) scale(.4)`, opacity: 0 },
        ], { duration: 700 + Math.random() * 200, easing: 'cubic-bezier(0.23, 1, 0.32, 1)' }).onfinish = () => dot.remove();
      }
    };
    new MutationObserver(() => {
      if (status.dataset.state === 'success' && status.textContent) burst();
    }).observe(status, { attributes: true, attributeFilter: ['data-state'], childList: true });
  }
})();
