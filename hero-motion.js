// Hero entrance, part 2: the terminal types its lines once the window has arrived,
// and on a mouse the window and the terminal drift with the pointer at two depths.
// Nothing runs with reduced motion: the terminal is then shown complete.
(() => {
  const hero = document.querySelector('.hero');
  if (!hero || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  // terminal typewriter
  const term = hero.querySelector('.hero-term');
  const code = term?.querySelector('code');
  if (code) {
    const walker = document.createTreeWalker(code, NodeFilter.SHOW_TEXT);
    const nodes = [];
    while (walker.nextNode()) nodes.push({ node: walker.currentNode, text: walker.currentNode.textContent });
    // keep the final size while typing, so the box does not grow line by line
    term.style.width = `${term.offsetWidth}px`;
    term.style.height = `${term.offsetHeight}px`;
    nodes.forEach(n => { n.node.textContent = ''; });
    const caret = document.createElement('span');
    caret.className = 'term-caret';
    code.append(caret);

    let n = 0, c = 0;
    const type = () => {
      if (n >= nodes.length) {
        term.style.width = '';
        term.style.height = '';
        term.classList.add('is-typed');
        return;
      }
      const { node, text } = nodes[n];
      const char = text[c];
      node.textContent += char;
      node.parentNode.insertBefore(caret, node.nextSibling);
      c += 1;
      if (c >= text.length) { n += 1; c = 0; }
      // prompts and line breaks pause like someone typing a command
      const delay = char === '\n' ? 180 : char === '$' ? 90 : 12 + Math.random() * 18;
      setTimeout(type, delay);
    };
    setTimeout(type, 1300);
  }

  // pointer parallax: window and terminal at two depths (uses `translate`, so CSS transforms keep working)
  if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
  const layers = [
    [hero.querySelector('.hero-window'), -10, -8],
    [term, 16, 12],
    [hero.querySelector('.hero-school'), 4, 3],
  ].filter(([el]) => el);
  let tx = 0, ty = 0, x = 0, y = 0, frame = 0;
  const loop = () => {
    x += (tx - x) * 0.08;
    y += (ty - y) * 0.08;
    layers.forEach(([el, kx, ky]) => { el.style.translate = `${(x * kx).toFixed(2)}px ${(y * ky).toFixed(2)}px`; });
    frame = Math.abs(tx - x) > 0.001 || Math.abs(ty - y) > 0.001 ? requestAnimationFrame(loop) : 0;
  };
  hero.addEventListener('pointermove', event => {
    const rect = hero.getBoundingClientRect();
    tx = ((event.clientX - rect.left) / rect.width - 0.5) * 2;
    ty = ((event.clientY - rect.top) / rect.height - 0.5) * 2;
    if (!frame) frame = requestAnimationFrame(loop);
  }, { passive: true });
  hero.addEventListener('pointerleave', () => {
    tx = 0; ty = 0;
    if (!frame) frame = requestAnimationFrame(loop);
  });
})();
