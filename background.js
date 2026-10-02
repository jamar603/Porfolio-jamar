// animated site background: drifting aurora glows + a particle network that reacts to the cursor
(() => {
  const canvas = document.createElement('canvas');
  canvas.className = 'bg-canvas';
  canvas.setAttribute('aria-hidden', 'true');
  document.body.prepend(canvas);

  const ctx = canvas.getContext('2d');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const auroras = [
    { color: '96,165,250', radius: 0.42, speed: 0.00006, phase: 0, ox: 0.18, oy: 0.22 },
    { color: '165,180,252', radius: 0.5, speed: 0.000045, phase: 2.1, ox: 0.78, oy: 0.35 },
    { color: '242,139,130', radius: 0.32, speed: 0.00007, phase: 4.2, ox: 0.6, oy: 0.82 }
  ];
  const mouse = { x: -9999, y: -9999, active: false };
  let width = 0;
  let height = 0;
  let dpr = 1;
  let particles = [];
  let frame = 0;

  const resize = () => {
    dpr = Math.min(window.devicePixelRatio || 1, 1.5);
    width = window.innerWidth;
    height = window.innerHeight;
    canvas.width = Math.round(width * dpr);
    canvas.height = Math.round(height * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

    const count = Math.min(Math.round((width * height) / 15000), 95);
    particles = Array.from({ length: count }, () => ({
      x: Math.random() * width,
      y: Math.random() * height,
      vx: (Math.random() - 0.5) * 0.22,
      vy: (Math.random() - 0.5) * 0.22,
      r: Math.random() * 1.4 + 0.6,
      hue: Math.random() < 0.7 ? '96,165,250' : '165,180,252'
    }));
  };

  const draw = time => {
    ctx.clearRect(0, 0, width, height);

    // aurora glows
    ctx.globalCompositeOperation = 'lighter';
    const scrollShift = window.scrollY * 0.08;
    auroras.forEach(a => {
      const t = time * a.speed + a.phase;
      const x = (a.ox + Math.sin(t) * 0.12) * width;
      const y = (a.oy + Math.cos(t * 1.3) * 0.1) * height - (scrollShift % height) * 0.2;
      const r = a.radius * Math.max(width, height) * (0.9 + Math.sin(t * 2) * 0.1);
      const gradient = ctx.createRadialGradient(x, y, 0, x, y, r);
      gradient.addColorStop(0, `rgba(${a.color},0.075)`);
      gradient.addColorStop(1, `rgba(${a.color},0)`);
      ctx.fillStyle = gradient;
      ctx.fillRect(0, 0, width, height);
    });

    // cursor spotlight
    if (mouse.active) {
      const spot = ctx.createRadialGradient(mouse.x, mouse.y, 0, mouse.x, mouse.y, 260);
      spot.addColorStop(0, 'rgba(96,165,250,0.07)');
      spot.addColorStop(1, 'rgba(96,165,250,0)');
      ctx.fillStyle = spot;
      ctx.fillRect(mouse.x - 260, mouse.y - 260, 520, 520);
    }
    ctx.globalCompositeOperation = 'source-over';

    // particle network
    const linkDistance = 130;
    for (let i = 0; i < particles.length; i += 1) {
      const p = particles[i];
      if (!reducedMotion.matches) {
        const dx = mouse.x - p.x;
        const dy = mouse.y - p.y;
        const dist = Math.hypot(dx, dy);
        if (mouse.active && dist < 180 && dist > 0.1) {
          // gentle pull toward the cursor
          p.vx += (dx / dist) * 0.012;
          p.vy += (dy / dist) * 0.012;
        }
        p.vx = Math.max(Math.min(p.vx * 0.992, 0.6), -0.6);
        p.vy = Math.max(Math.min(p.vy * 0.992, 0.6), -0.6);
        if (Math.abs(p.vx) < 0.05) p.vx += (Math.random() - 0.5) * 0.02;
        if (Math.abs(p.vy) < 0.05) p.vy += (Math.random() - 0.5) * 0.02;
        p.x += p.vx;
        p.y += p.vy;
        if (p.x < -20) p.x = width + 20; else if (p.x > width + 20) p.x = -20;
        if (p.y < -20) p.y = height + 20; else if (p.y > height + 20) p.y = -20;
      }

      for (let j = i + 1; j < particles.length; j += 1) {
        const q = particles[j];
        const d = Math.hypot(p.x - q.x, p.y - q.y);
        if (d < linkDistance) {
          ctx.strokeStyle = `rgba(${p.hue},${(1 - d / linkDistance) * 0.16})`;
          ctx.lineWidth = 1;
          ctx.beginPath();
          ctx.moveTo(p.x, p.y);
          ctx.lineTo(q.x, q.y);
          ctx.stroke();
        }
      }

      if (mouse.active) {
        const d = Math.hypot(p.x - mouse.x, p.y - mouse.y);
        if (d < 170) {
          ctx.strokeStyle = `rgba(96,165,250,${(1 - d / 170) * 0.35})`;
          ctx.beginPath();
          ctx.moveTo(p.x, p.y);
          ctx.lineTo(mouse.x, mouse.y);
          ctx.stroke();
        }
      }

      ctx.fillStyle = `rgba(${p.hue},0.7)`;
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fill();
    }
  };

  const loop = time => {
    draw(time);
    frame = requestAnimationFrame(loop);
  };

  const start = () => {
    cancelAnimationFrame(frame);
    frame = 0;
    if (reducedMotion.matches) draw(0);
    else if (!document.hidden) frame = requestAnimationFrame(loop);
  };

  window.addEventListener('pointermove', event => {
    if (event.pointerType !== 'mouse') return;
    mouse.x = event.clientX;
    mouse.y = event.clientY;
    mouse.active = true;
  }, { passive: true });
  document.addEventListener('mouseout', event => { if (!event.relatedTarget) mouse.active = false; });
  window.addEventListener('resize', () => { resize(); if (reducedMotion.matches) draw(0); });
  document.addEventListener('visibilitychange', start);
  reducedMotion.addEventListener('change', start);

  resize();
  start();
})();
