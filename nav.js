const menuButton = document.querySelector('.nav-menu-toggle');
const navMenu = document.getElementById('siteNavLinks');

if (menuButton && navMenu){
  const setMenuOpen = (open, returnFocus = false) => {
    menuButton.setAttribute('aria-expanded', String(open));
    menuButton.querySelector('.menu-toggle-label').textContent = open ? 'Fermer' : 'Menu';
    navMenu.classList.toggle('is-open', open);

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