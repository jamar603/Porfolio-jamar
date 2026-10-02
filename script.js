// nav active state
const navLinks = document.querySelectorAll('.nav-links a[data-t]');
const sections = [...navLinks].map(a => document.getElementById(a.dataset.t)).filter(Boolean);
const navObs = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting){
      navLinks.forEach(a => a.classList.toggle('active', a.dataset.t === entry.target.id));
    }
  });
}, { rootMargin: '-45% 0px -50% 0px' });
sections.forEach(s => navObs.observe(s));

const contactForm = document.getElementById('contactForm');
contactForm?.addEventListener('submit', event => {
  event.preventDefault();
  const formData = new FormData(contactForm);
  const subject = encodeURIComponent(formData.get('subject').trim());
  const body = encodeURIComponent(
    `Nom : ${formData.get('name')}\nE-mail : ${formData.get('email')}\n\n${formData.get('message')}`
  );
  window.location.href = `mailto:jamarcarty131@gmail.com?subject=${subject}&body=${body}`;
});


// scroll reveal
const revealTargets = document.querySelectorAll('.section-head, .profile-grid, .branch-card, .t-row, .project-card, .skill-group, .veille-grid, .contact-wrap');
if ('IntersectionObserver' in window){
  const revealObs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting){
        entry.target.classList.add('is-visible');
        revealObs.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
  revealTargets.forEach(el => {
    const siblings = [...el.parentElement.children].filter(c => c.matches('.branch-card, .project-card, .skill-group, .t-row'));
    const index = siblings.indexOf(el);
    if (index > 0) el.style.setProperty('--reveal-delay', `${Math.min(index, 5) * 70}ms`);
    el.classList.add('reveal');
    revealObs.observe(el);
  });
}
