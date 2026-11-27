// Formation and Expérience: hovering (or tapping) a block lights up its point and the curve around it
(() => {
  // Expérience: card i <-> rail dot i
  const xCards = [...document.querySelectorAll('.xpath-card')];
  const xDots = [...document.querySelectorAll('.xpath-dot')];
  const xLines = [...document.querySelectorAll('.xpath-line')];

  // Formation: node i sits between curve segment i (before) and i + 1 (after)
  const fNodes = [...document.querySelectorAll('.fpath-node, .fpath-goal')];
  const fLines = [...document.querySelectorAll('.fpath-line')];

  const light = (items, on) => items.forEach((el) => el && el.classList.toggle('is-hot', on));

  const wire = (cards, partsFor) => {
    const offs = [];
    cards.forEach((card, i) => {
      const parts = partsFor(i);
      const on = () => { cards.forEach((c) => c.classList.toggle('is-dim', c !== card)); card.classList.add('is-hot'); light(parts, true); };
      const off = () => { cards.forEach((c) => c.classList.remove('is-dim')); card.classList.remove('is-hot'); light(parts, false); };
      offs.push(off);
      card.addEventListener('pointerenter', (e) => { if (e.pointerType === 'mouse') on(); });
      card.addEventListener('pointerleave', (e) => { if (e.pointerType === 'mouse') off(); });
      card.addEventListener('focusin', on);
      card.addEventListener('focusout', (e) => { if (!card.contains(e.relatedTarget)) off(); });
      // touch: a tap toggles the highlight (links and buttons inside keep working)
      card.addEventListener('click', (e) => {
        if (e.target.closest('a, button') || window.matchMedia('(hover: hover)').matches) return;
        if (card.classList.contains('is-hot')) { off(); return; }
        offs.forEach((fn) => fn());
        on();
      });
    });
  };

  wire(xCards, (i) => [xDots[i], xLines[i - 1], xLines[i]]);
  wire(fNodes, (i) => [fLines[i - 1], fLines[i]]);
})();

// Tableau de synthèse: hovering a cell lights its whole column (the row is already lit), so a cross shows
// at a glance which competence a tick belongs to
(() => {
  const table = document.querySelector('.synth-table');
  if (!table || !window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
  let col = null;
  const set = (next) => {
    if (next === col) return;
    if (col) table.querySelectorAll(`[data-col="${col}"]`).forEach((el) => el.classList.remove('is-cross'));
    col = next;
    if (col) table.querySelectorAll(`[data-col="${col}"]`).forEach((el) => el.classList.add('is-cross'));
  };
  table.addEventListener('pointerover', (e) => set(e.target.closest('[data-col]')?.dataset.col || null));
  table.addEventListener('pointerleave', () => set(null));
})();
