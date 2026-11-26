// small personal touches: a message in the browser console for curious developers,
// and a hidden Konami code (↑ ↑ ↓ ↓ ← → ← → B A) that throws confetti and thanks the jury
(() => {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  // console greeting (members of the jury sometimes open the developer tools)
  console.log(
    '%c👋 Bonjour !%c\nVous inspectez le code : bonne idée.\nPortfolio écrit à la main en HTML, CSS, JavaScript et PHP, sans framework.\nIndice : essayez le code Konami ↑ ↑ ↓ ↓ ← → ← → B A',
    'font:700 18px system-ui;color:#60a5fa',
    'font:13px system-ui;color:inherit',
  );

  const toast = (text) => {
    const el = document.createElement('div');
    el.className = 'fun-toast';
    el.setAttribute('role', 'status');
    el.textContent = text;
    document.body.append(el);
    requestAnimationFrame(() => el.classList.add('is-in'));
    setTimeout(() => { el.classList.remove('is-in'); setTimeout(() => el.remove(), 400); }, 3600);
  };

  const confetti = () => {
    if (reducedMotion.matches) return;
    const colors = ['#60a5fa', '#a5b4fc', '#f28b82', '#e5b567', '#6ee7b7'];
    for (let i = 0; i < 70; i++) {
      const bit = document.createElement('span');
      bit.className = 'fun-confetti';
      bit.style.left = `${Math.random() * 100}vw`;
      bit.style.background = colors[i % colors.length];
      bit.style.width = `${6 + Math.random() * 6}px`;
      bit.style.height = `${8 + Math.random() * 8}px`;
      document.body.append(bit);
      const drift = (Math.random() - 0.5) * 240;
      const spin = (Math.random() - 0.5) * 1080;
      bit.animate([
        { transform: 'translate3d(0, -20px, 0) rotate(0deg)', opacity: 1 },
        { transform: `translate3d(${drift}px, 105vh, 0) rotate(${spin}deg)`, opacity: 0.9 },
      ], { duration: 2200 + Math.random() * 1600, delay: Math.random() * 400, easing: 'cubic-bezier(.2,.6,.4,1)', fill: 'forwards' })
        .onfinish = () => bit.remove();
    }
  };

  const code = ['ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight', 'b', 'a'];
  let pos = 0;
  document.addEventListener('keydown', (event) => {
    // not while typing, nor in presentation mode (arrows change slides there)
    if (event.target.closest('input, textarea, select, [contenteditable="true"]')) return;
    if (document.documentElement.classList.contains('is-presenting')) return;
    const key = event.key.length === 1 ? event.key.toLowerCase() : event.key;
    pos = key === code[pos] ? pos + 1 : (key === code[0] ? 1 : 0);
    if (pos === code.length) {
      pos = 0;
      confetti();
      toast('🎉 Code Konami trouvé ! Merci au jury pour sa curiosité.');
    }
  });
})();
