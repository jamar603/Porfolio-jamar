// nav active state
const navLinks = document.querySelectorAll('.nav-links a[data-t]');
const sections = [...navLinks].map(a => document.getElementById(a.dataset.t)).filter(Boolean);
const navObs = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting){
      navLinks.forEach(a => a.classList.toggle('active', a.dataset.t === entry.target.id));
    }
  });
}, { threshold: 0.4 });
sections.forEach(s => navObs.observe(s));

