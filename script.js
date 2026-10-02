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
    el.querySelectorAll('.project-tags span').forEach((tag, i) => tag.style.setProperty('--i', Math.min(i, 8)));
    el.classList.add('reveal');
    revealObs.observe(el);
  });
}

// pointer spotlight on cards: write coordinates on the hovered card only
if (window.matchMedia('(hover: hover) and (pointer: fine)').matches){
  document.addEventListener('pointermove', (event) => {
    const card = event.target instanceof Element ? event.target.closest('.project-card, .article-card') : null;
    if (!card) return;
    const rect = card.getBoundingClientRect();
    card.style.setProperty('--mx', `${event.clientX - rect.left}px`);
    card.style.setProperty('--my', `${event.clientY - rect.top}px`);
  }, { passive: true });
}

// 3D tilt: cards lean toward the pointer, smoothed every frame so motion keeps its momentum
(() => {
  const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (!finePointer.matches || reducedMotion.matches) return;

  const SELECTOR = '.project-card, .article-card, .branch-card, #experience .t-content';
  const MAX_X = 7;
  const MAX_Y = 9;
  const LIFT = 4;
  const SMOOTHING = 0.14;
  const states = new Map();
  let frame = 0;

  const release = (state) => {
    state.hover = false;
    state.tx = 0;
    state.ty = 0;
    state.tl = 0;
  };

  const loop = () => {
    frame = 0;
    states.forEach((s, card) => {
      s.rx += (s.tx - s.rx) * SMOOTHING;
      s.ry += (s.ty - s.ry) * SMOOTHING;
      s.lift += (s.tl - s.lift) * SMOOTHING;
      if (!s.hover && Math.abs(s.rx) < 0.02 && Math.abs(s.ry) < 0.02 && s.lift < 0.05) {
        card.style.transform = '';
        card.classList.remove('is-tilting');
        states.delete(card);
        return;
      }
      card.style.transform = `perspective(900px) translateY(${-s.lift}px) rotateX(${s.rx}deg) rotateY(${s.ry}deg)`;
    });
    if (states.size) frame = requestAnimationFrame(loop);
  };
  const kick = () => { if (!frame) frame = requestAnimationFrame(loop); };

  document.addEventListener('pointermove', (event) => {
    const target = event.target instanceof Element ? event.target.closest(SELECTOR) : null;
    // wait for the scroll reveal to finish before taking over the transform
    const card = target && !(target.classList.contains('reveal') && !target.classList.contains('is-visible')) ? target : null;
    states.forEach((s, c) => { if (c !== card) release(s); });
    if (card) {
      let s = states.get(card);
      if (!s) {
        s = { rx: 0, ry: 0, lift: 0 };
        states.set(card, s);
        card.classList.add('is-tilting');
      }
      const rect = card.getBoundingClientRect();
      const px = (event.clientX - rect.left) / rect.width - 0.5;
      const py = (event.clientY - rect.top) / rect.height - 0.5;
      s.hover = true;
      s.tx = -py * MAX_X * 2;
      s.ty = px * MAX_Y * 2;
      s.tl = LIFT;
    }
    if (states.size) kick();
  }, { passive: true });

  document.addEventListener('pointerleave', () => {
    states.forEach(release);
    kick();
  });
})();
