// Site menu: four groups (Épreuve E5, Parcours, Réalisations, Compétences) with drop-down panels,
// a Contact button, a sliding highlight on the active group, a scroll progress bar,
// and a full-screen menu on phones and tablets.
const menuButton = document.querySelector('.nav-menu-toggle');
const navMenu = document.getElementById('siteNavLinks');
const siteNav = document.querySelector('body > nav');
const desktopMenu = window.matchMedia('(min-width: 961px)');

// ---------- phones / tablets: full-screen menu ----------
if (menuButton && navMenu){
  const setMenuOpen = (open, returnFocus = false) => {
    menuButton.setAttribute('aria-expanded', String(open));
    menuButton.querySelector('.menu-toggle-label').textContent = open ? 'Fermer' : 'Menu';
    navMenu.classList.toggle('is-open', open);
    siteNav?.classList.toggle('menu-open', open);
    document.documentElement.classList.toggle('menu-open', open);
    if (open) navMenu.querySelector('a')?.focus({ preventScroll: true });
    else if (returnFocus) menuButton.focus();
  };
  menuButton.addEventListener('click', () => setMenuOpen(menuButton.getAttribute('aria-expanded') !== 'true'));
  navMenu.addEventListener('click', event => { if (event.target.closest('a, [data-present-start]')) setMenuOpen(false); });
  document.querySelector('nav .logo')?.addEventListener('click', () => setMenuOpen(false));
  document.addEventListener('click', event => {
    if (menuButton.getAttribute('aria-expanded') === 'true' && !event.target.closest('nav')) setMenuOpen(false);
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && menuButton.getAttribute('aria-expanded') === 'true') setMenuOpen(false, true);
  });
  desktopMenu.addEventListener('change', event => { if (event.matches) setMenuOpen(false); });
  // stagger order for the full-screen menu entrance
  [...navMenu.querySelectorAll('.menu-top, .menu-panel a, .menu-end a')].forEach((el, i) => el.style.setProperty('--i', i));
}

// ---------- scroll progress + header shadow ----------
const progressBar = document.createElement('div');
progressBar.className = 'scroll-progress';
progressBar.setAttribute('aria-hidden', 'true');
document.body.prepend(progressBar);
let scrollTicking = false;
const updateScrollUi = () => {
  const max = document.documentElement.scrollHeight - window.innerHeight;
  progressBar.style.transform = `scaleX(${max > 0 ? Math.min(window.scrollY / max, 1) : 0})`;
  siteNav?.classList.toggle('is-scrolled', window.scrollY > 8);
  scrollTicking = false;
};
window.addEventListener('scroll', () => {
  if (!scrollTicking){ scrollTicking = true; requestAnimationFrame(updateScrollUi); }
}, { passive: true });
updateScrollUi();

// ---------- computer: drop-down panels ----------
if (navMenu){
  const groups = [...navMenu.querySelectorAll('.menu-group')];
  const list = navMenu.querySelector('.menu-list');
  let openGroup = null;
  let closeTimer = 0;

  const setOpen = (group, focusFirst = false) => {
    clearTimeout(closeTimer);
    groups.forEach(g => {
      const on = g === group;
      g.classList.toggle('is-open', on);
      g.querySelector('.menu-top').setAttribute('aria-expanded', String(on));
    });
    openGroup = group;
    if (group && focusFirst) group.querySelector('.menu-panel a')?.focus();
    moveIndicator();
  };
  const closeSoon = () => {
    clearTimeout(closeTimer);
    closeTimer = setTimeout(() => setOpen(null), 220);
  };

  groups.forEach(group => {
    const top = group.querySelector('.menu-top');
    // hover opens on a computer (short delay to avoid opening while the pointer only passes over)
    let openTimer = 0;
    group.addEventListener('pointerenter', event => {
      if (!desktopMenu.matches || event.pointerType === 'touch') return;
      clearTimeout(closeTimer);
      openTimer = setTimeout(() => setOpen(group), openGroup ? 0 : 90);
    });
    group.addEventListener('pointerleave', event => {
      if (!desktopMenu.matches || event.pointerType === 'touch') return;
      clearTimeout(openTimer);
      closeSoon();
    });
    // click / Enter / Space toggles (touch screens and keyboard)
    top.addEventListener('click', () => {
      if (!desktopMenu.matches) return;
      setOpen(openGroup === group ? null : group);
    });
    top.addEventListener('keydown', event => {
      if (!desktopMenu.matches) return;
      if (event.key === 'ArrowDown') { event.preventDefault(); setOpen(group, true); }
    });
    // arrows move inside an open panel
    group.querySelector('.menu-panel').addEventListener('keydown', event => {
      const items = [...group.querySelectorAll('.menu-panel a, .menu-panel button')];
      const i = items.indexOf(document.activeElement);
      if (event.key === 'ArrowDown') { event.preventDefault(); items[(i + 1) % items.length]?.focus(); }
      if (event.key === 'ArrowUp') { event.preventDefault(); (i <= 0 ? top : items[i - 1]).focus(); }
    });
  });
  // a choice closes the panel
  navMenu.addEventListener('click', event => {
    if (event.target.closest('.menu-panel a, .menu-panel button')) setOpen(null);
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && openGroup){
      const top = openGroup.querySelector('.menu-top');
      setOpen(null);
      top.focus();
    }
  });
  document.addEventListener('click', event => { if (openGroup && !event.target.closest('.menu-group')) setOpen(null); });
  navMenu.addEventListener('focusout', event => {
    if (openGroup && !openGroup.contains(event.relatedTarget)) closeSoon();
  });
  desktopMenu.addEventListener('change', () => setOpen(null));

  // sliding highlight: the open group, otherwise the group of the section on screen
  const indicator = document.createElement('span');
  indicator.className = 'nav-indicator';
  indicator.setAttribute('aria-hidden', 'true');
  list?.prepend(indicator);
  function moveIndicator(){
    if (!list) return;
    const activeGroup = openGroup || groups.find(g => g.querySelector('a.active'));
    groups.forEach(g => g.classList.toggle('has-active', Boolean(g.querySelector('a.active'))));
    const target = activeGroup?.querySelector('.menu-top');
    if (!target || !target.offsetWidth){ indicator.classList.remove('is-visible'); return; }
    const first = !indicator.classList.contains('is-visible');
    if (first) indicator.style.transition = 'none';
    indicator.style.width = `${target.offsetWidth}px`;
    // position measured against the whole bar (offsetLeft would be relative to the group itself)
    const x = target.getBoundingClientRect().left - list.getBoundingClientRect().left - list.clientLeft;
    indicator.style.transform = `translateX(${x}px)`;
    indicator.classList.add('is-visible');
    if (first){ indicator.offsetWidth; indicator.style.transition = ''; }
  }
  new MutationObserver(records => {
    if (records.some(r => r.target !== indicator && r.target.tagName === 'A')) moveIndicator();
  }).observe(navMenu, { subtree: true, attributes: true, attributeFilter: ['class'] });
  window.addEventListener('resize', moveIndicator);
  document.fonts?.ready.then(moveIndicator);
  moveIndicator();
}
