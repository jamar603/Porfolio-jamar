// Section entrances: every section (except the hero and the conclusion, which have their own sequence)
// plays a short choreography each time it is reached: title and underline first, then its blocks one
// after the other. A section that leaves the screen resets, so the sequence plays again next time
// (also when the presentation mode moves to it). With reduced motion everything is shown as is.
(() => {
  if (!('IntersectionObserver' in window)) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  const OWN_SEQUENCE = ['accueil', 'conclusion'];
  const MAX_STEP = 10;
  const sections = [...document.querySelectorAll('body > section[id]')].filter(s => !OWN_SEQUENCE.includes(s.id));

  // blocks animated by this script: the container's children, or the cards of a grid / flex row;
  // anything already driven by the scroll reveal (.reveal) keeps its own animation
  const isLayoutRow = el => {
    const d = getComputedStyle(el).display;
    return (d === 'grid' || d === 'flex') && el.children.length > 1;
  };
  const skip = el => el.matches('script, template, dialog, [hidden], .section-head') || el.closest('.reveal') || el.querySelector(':scope > .reveal');

  // pass 1, reads only: which blocks animate, in which order
  const plan = sections.map(section => {
    const container = section.querySelector(':scope > .container') || section;
    const items = [];
    [...container.children].forEach(block => {
      // a row of revealed cards replays with the section: nothing to add
      if (skip(block)) return;
      items.push(...(isLayoutRow(block) ? [...block.children].filter(c => !skip(c)) : [block]));
    });
    return [section, items];
  });
  // pass 2, writes only
  plan.forEach(([section, items]) => {
    items.forEach((item, step) => {
      item.classList.add('sx-item');
      item.style.setProperty('--sx', Math.min(step, MAX_STEP));
    });
    section.classList.add('sx');
  });

  const play = section => {
    section.classList.add('is-in');
    // the scroll reveal blocks (cards, timeline rows…) replay with the section
    section.querySelectorAll('.reveal').forEach(el => el.classList.add('is-visible'));
  };
  const reset = section => {
    section.classList.remove('is-in');
    section.querySelectorAll('.reveal').forEach(el => el.classList.remove('is-visible'));
  };

  // enters when its top passes 75 % of the screen (or as soon as any part shows when coming from below)
  const enterObs = new IntersectionObserver(entries => {
    entries.forEach(entry => { if (entry.isIntersecting) play(entry.target); });
  }, { rootMargin: '0px 0px -25% 0px' });
  // resets only once completely off screen
  const leaveObs = new IntersectionObserver(entries => {
    entries.forEach(entry => { if (!entry.isIntersecting) reset(entry.target); });
  });
  sections.forEach(section => {
    enterObs.observe(section);
    leaveObs.observe(section);
    // presentation mode: replay the whole sequence when the section becomes the current step
    section.addEventListener('present:enter', () => {
      reset(section);
      void section.offsetWidth;
      play(section);
    });
  });
})();
