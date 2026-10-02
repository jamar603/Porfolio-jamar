const menuButton = document.querySelector('.nav-menu-toggle');
const navMenu = document.getElementById('siteNavLinks');

if (menuButton && navMenu){
  const setMenuOpen = (open, returnFocus = false) => {
    menuButton.setAttribute('aria-expanded', String(open));
    menuButton.querySelector('.menu-toggle-label').textContent = open ? 'Fermer' : 'Menu';
    navMenu.classList.toggle('is-open', open);
    menuButton.closest('nav')?.classList.toggle('menu-open', open);
    document.documentElement.classList.toggle('menu-open', open);

    if (open) navMenu.querySelector('a')?.focus();
    else if (returnFocus) menuButton.focus();
  };

  menuButton.addEventListener('click', () => {
    setMenuOpen(menuButton.getAttribute('aria-expanded') !== 'true');
  });

  navMenu.addEventListener('click', event => {
    if (event.target.closest('a')) setMenuOpen(false);
  });

  document.addEventListener('click', event => {
    if (menuButton.getAttribute('aria-expanded') === 'true' && !event.target.closest('nav')){
      setMenuOpen(false);
    }
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && menuButton.getAttribute('aria-expanded') === 'true'){
      setMenuOpen(false, true);
    }
  });

  window.matchMedia('(min-width: 961px)').addEventListener('change', event => {
    if (event.matches) setMenuOpen(false);
  });
}
const siteNav = document.querySelector('nav');
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
  if (!scrollTicking){
    scrollTicking = true;
    requestAnimationFrame(updateScrollUi);
  }
}, { passive: true });
updateScrollUi();

// sliding highlight behind the hovered / active desktop link
if (navMenu){
  const navItems = [...navMenu.querySelectorAll('a')];
  const sectionItems = navItems.filter(a => !a.matches('.nav-github, .nav-linkedin'));
  [...sectionItems, ...navItems.filter(a => !sectionItems.includes(a))].forEach((a, i) => a.style.setProperty('--i', i));

  const indicator = document.createElement('span');
  indicator.className = 'nav-indicator';
  indicator.setAttribute('aria-hidden', 'true');
  navMenu.prepend(indicator);

  let hovered = null;
  const moveIndicator = () => {
    const target = hovered || navMenu.querySelector('a.active');
    if (!target){
      indicator.classList.remove('is-visible');
      return;
    }
    const first = !indicator.classList.contains('is-visible');
    if (first) indicator.style.transition = 'none';
    indicator.style.width = `${target.offsetWidth}px`;
    indicator.style.transform = `translateX(${target.offsetLeft}px)`;
    indicator.classList.add('is-visible');
    if (first){
      indicator.offsetWidth;
      indicator.style.transition = '';
    }
  };

  navItems.forEach(a => {
    a.addEventListener('pointerenter', () => { hovered = a; moveIndicator(); });
    a.addEventListener('focus', () => { hovered = a; moveIndicator(); });
    a.addEventListener('blur', () => { hovered = null; moveIndicator(); });
  });
  navMenu.addEventListener('pointerleave', () => { hovered = null; moveIndicator(); });
  new MutationObserver(records => { if (records.some(r => r.target !== indicator)) moveIndicator(); }).observe(navMenu, { subtree: true, attributes: true, attributeFilter: ['class'] });
  window.addEventListener('resize', moveIndicator);
  document.fonts?.ready.then(moveIndicator);
  moveIndicator();
}
