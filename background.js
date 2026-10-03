// animated site background: water — spring-simulated wave layers, caustic light, rising bubbles and cursor ripples
(() => {
  const canvas = document.createElement('canvas');
  canvas.className = 'bg-canvas';
  canvas.setAttribute('aria-hidden', 'true');
  document.body.prepend(canvas);

  const ctx = canvas.getContext('2d');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const COLUMN = 10; // horizontal spacing of the simulated water columns, in px
  const STEP = 1 / 60; // fixed simulation step, so motion is identical at 60, 120 or 144 Hz

  // wave layers, back to front. depth drives parallax and how strongly the cursor disturbs the layer
  const layers = [
    { y: 0.34, amp: 30, freqs: [0.0041, 0.0093, 0.0017], speeds: [0.32, -0.21, 0.13], color: '165,180,252', alpha: 0.05, depth: 0.25 },
    { y: 0.48, amp: 38, freqs: [0.0031, 0.0072, 0.0013], speeds: [-0.38, 0.27, -0.11], color: '96,165,250', alpha: 0.06, depth: 0.45 },
    { y: 0.62, amp: 28, freqs: [0.0053, 0.0118, 0.0021], speeds: [0.46, -0.31, 0.17], color: '96,165,250', alpha: 0.07, depth: 0.65 },
    { y: 0.76, amp: 36, freqs: [0.0036, 0.0081, 0.0015], speeds: [-0.34, 0.24, -0.14], color: '165,180,252', alpha: 0.08, depth: 0.85 },
    { y: 0.88, amp: 22, freqs: [0.0059, 0.0131, 0.0024], speeds: [0.52, -0.36, 0.19], color: '96,165,250', alpha: 0.09, depth: 1 }
  ];
  const mouse = { x: 0, y: 0, vx: 0, vy: 0, active: false, last: 0 };
  const scroll = { current: window.scrollY, velocity: 0, swell: 0 };
  let width = 0;
  let height = 0;
  let dpr = 1;
  let columns = 0;
  let bubbles = [];
  let ripples = [];
  let lastRipple = 0;
  let frame = 0;
  let clock = 0; // simulation time in seconds
  let previous = 0;
  let accumulator = 0;
  let fadeIn = 0;

  const easeOutCubic = t => 1 - (1 - t) ** 3;
  const damp = (from, to, rate, dt) => to + (from - to) * Math.exp(-rate * dt);

  const makeBubble = fromBottom => ({
    x: Math.random() * width,
    y: fromBottom ? height + 10 + Math.random() * 40 : Math.random() * height,
    r: Math.random() * 2.2 + 0.8,
    speed: 0, // px/s, eases up to maxSpeed so bubbles do not start abruptly
    maxSpeed: Math.random() * 18 + 10,
    phase: Math.random() * Math.PI * 2,
    wobble: Math.random() * 0.8 + 0.6
  });

  const resize = () => {
    dpr = Math.min(window.devicePixelRatio || 1, 1.5);
    width = window.innerWidth;
    height = window.innerHeight;
    canvas.width = Math.round(width * dpr);
    canvas.height = Math.round(height * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

    columns = Math.ceil(width / COLUMN) + 2;
    layers.forEach(layer => {
      layer.h = new Float32Array(columns); // displacement from the resting wave
      layer.v = new Float32Array(columns); // vertical velocity
    });
    const count = Math.min(Math.round((width * height) / 26000), 60);
    bubbles = Array.from({ length: count }, () => makeBubble(false));
  };

  // resting wave shape: three detuned sines, so the pattern never visibly repeats
  const baseSurface = (layer, x) => {
    const [f1, f2, f3] = layer.freqs;
    const [s1, s2, s3] = layer.speeds;
    const breathe = 1 + Math.sin(clock * 0.21 + layer.depth * 4) * 0.18 + scroll.swell * layer.depth;
    return layer.y * height + layer.amp * breathe * (
      Math.sin(x * f1 + clock * s1) +
      Math.sin(x * f2 + clock * s2) * 0.32 +
      Math.sin(x * f3 + clock * s3) * 0.55
    ) / 1.87;
  };

  const parallax = layer => -Math.min(scroll.current, height * 2) * 0.05 * layer.depth;

  const surfaceAt = (layer, x) => {
    const i = Math.max(0, Math.min(columns - 1, Math.round(x / COLUMN)));
    return baseSurface(layer, x) + layer.h[i] + parallax(layer);
  };

  // one fixed step of the water springs: each column pulls back to rest and passes energy to its neighbours
  const simulate = dt => {
    clock += dt;

    const target = window.scrollY;
    const before = scroll.current;
    scroll.current = damp(scroll.current, target, 8, dt);
    scroll.velocity = (scroll.current - before) / dt;
    scroll.swell = damp(scroll.swell, Math.min(Math.abs(scroll.velocity) / 2500, 0.6), 3, dt);

    layers.forEach(layer => {
      const { h, v } = layer;
      for (let i = 0; i < columns; i += 1) {
        v[i] += (-90 * h[i] - 2.2 * v[i]) * dt;
        h[i] += v[i] * dt;
      }
      for (let pass = 0; pass < 2; pass += 1) {
        for (let i = 1; i < columns - 1; i += 1) {
          v[i] += (h[i - 1] + h[i + 1] - 2 * h[i]) * 900 * dt;
        }
      }
    });

    bubbles.forEach((b, i) => {
      b.speed = damp(b.speed, b.maxSpeed, 1.5, dt);
      b.y -= b.speed * dt;
      b.x += Math.sin(clock * b.wobble * 2 + b.phase) * 8 * dt;
      if (b.y < -12) bubbles[i] = makeBubble(true);
    });

    ripples.forEach(r => { r.age += dt; });
    ripples = ripples.filter(r => r.age < r.duration);

    mouse.vx = damp(mouse.vx, 0, 10, dt);
    mouse.vy = damp(mouse.vy, 0, 10, dt);
    fadeIn = Math.min(1, fadeIn + dt / 1.2);
  };

  // push the water columns under the cursor, scaled by how fast it moves
  const disturb = (x, y, force, radius) => {
    layers.forEach(layer => {
      const gap = Math.abs(y - surfaceAt(layer, x));
      if (gap > 70) return;
      const near = 1 - gap / 70;
      const centre = Math.round(x / COLUMN);
      const span = Math.ceil(radius / COLUMN);
      for (let k = -span; k <= span; k += 1) {
        const i = centre + k;
        if (i < 0 || i >= columns) continue;
        const falloff = Math.cos((k / span) * Math.PI * 0.5);
        layer.v[i] += force * near * falloff * (0.5 + layer.depth * 0.5);
      }
    });
  };

  const draw = () => {
    ctx.clearRect(0, 0, width, height);
    ctx.globalAlpha = easeOutCubic(fadeIn);

    // caustic light drifting near the top
    ctx.globalCompositeOperation = 'lighter';
    for (let i = 0; i < 4; i += 1) {
      const t = clock * 0.11 + i * 1.7;
      const x = (0.15 + i * 0.24 + Math.sin(t) * 0.06) * width;
      const r = Math.max(width, height) * 0.35;
      const g = ctx.createRadialGradient(x, -r * 0.2, 0, x, -r * 0.2, r);
      g.addColorStop(0, `rgba(165,180,252,${0.05 + Math.sin(t * 2.3) * 0.015})`);
      g.addColorStop(1, 'rgba(165,180,252,0)');
      ctx.fillStyle = g;
      ctx.fillRect(0, 0, width, height);
    }
    ctx.globalCompositeOperation = 'source-over';

    // wave layers: soft body + bright crest
    layers.forEach(layer => {
      const points = [];
      for (let i = 0; i < columns; i += 1) {
        const x = i * COLUMN;
        points.push(x, baseSurface(layer, x) + layer.h[i] + parallax(layer));
      }

      const trace = () => {
        ctx.moveTo(points[0], points[1]);
        for (let p = 2; p < points.length - 2; p += 2) {
          // quadratic curve through midpoints keeps the surface smooth between columns
          ctx.quadraticCurveTo(points[p], points[p + 1], (points[p] + points[p + 2]) / 2, (points[p + 1] + points[p + 3]) / 2);
        }
        ctx.lineTo(points[points.length - 2], points[points.length - 1]);
      };

      ctx.beginPath();
      trace();
      ctx.lineTo(width, height);
      ctx.lineTo(0, height);
      ctx.closePath();
      const top = layer.y * height - layer.amp + parallax(layer);
      const fill = ctx.createLinearGradient(0, top, 0, height);
      fill.addColorStop(0, `rgba(${layer.color},${layer.alpha})`);
      fill.addColorStop(1, `rgba(${layer.color},0)`);
      ctx.fillStyle = fill;
      ctx.fill();

      ctx.beginPath();
      trace();
      ctx.strokeStyle = `rgba(${layer.color},${layer.alpha * 2.4})`;
      ctx.lineWidth = 1.2;
      ctx.stroke();
    });

    // rising bubbles, slightly squashing as they wobble
    bubbles.forEach(b => {
      const squash = 1 + Math.sin(clock * b.wobble * 4 + b.phase) * 0.12;
      ctx.strokeStyle = 'rgba(165,180,252,0.35)';
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.ellipse(b.x, b.y, b.r * squash, b.r / squash, 0, 0, Math.PI * 2);
      ctx.stroke();
      ctx.fillStyle = 'rgba(229,231,235,0.5)';
      ctx.beginPath();
      ctx.arc(b.x - b.r * 0.35, b.y - b.r * 0.35, b.r * 0.3, 0, Math.PI * 2);
      ctx.fill();
    });

    // ripple rings: fast start, slow settle, fading out
    ripples.forEach(r => {
      const p = r.age / r.duration;
      const radius = r.size * easeOutCubic(p);
      ctx.strokeStyle = `rgba(96,165,250,${r.strength * (1 - p) ** 2 * 0.45})`;
      ctx.lineWidth = 1.5 * (1 - p) + 0.4;
      ctx.beginPath();
      ctx.ellipse(r.x, r.y, radius, radius * 0.42, 0, 0, Math.PI * 2);
      ctx.stroke();
      if (r.strength > 0.8 && p > 0.12) {
        const inner = r.size * 0.6 * easeOutCubic((p - 0.12) / 0.88);
        ctx.beginPath();
        ctx.ellipse(r.x, r.y, inner, inner * 0.42, 0, 0, Math.PI * 2);
        ctx.stroke();
      }
    });

    ctx.globalAlpha = 1;
  };

  const loop = now => {
    const elapsed = previous ? Math.min((now - previous) / 1000, 0.1) : STEP;
    previous = now;
    accumulator += elapsed;
    while (accumulator >= STEP) {
      simulate(STEP);
      accumulator -= STEP;
    }
    draw();
    frame = requestAnimationFrame(loop);
  };

  const start = () => {
    cancelAnimationFrame(frame);
    frame = 0;
    previous = 0;
    accumulator = 0;
    if (reducedMotion.matches) {
      fadeIn = 1;
      draw();
    } else if (!document.hidden) {
      frame = requestAnimationFrame(loop);
    }
  };

  const addRipple = (x, y, strength) => {
    if (reducedMotion.matches || ripples.length > 12) return;
    ripples.push({ x, y, age: 0, strength, duration: 1.1 + strength * 0.9, size: 40 + strength * 70 });
  };

  window.addEventListener('pointermove', event => {
    if (event.pointerType !== 'mouse' || reducedMotion.matches) return;
    const now = performance.now();
    const dt = Math.max((now - mouse.last) / 1000, 0.008);
    if (mouse.active && now - mouse.last < 100) {
      mouse.vx = (event.clientX - mouse.x) / dt;
      mouse.vy = (event.clientY - mouse.y) / dt;
      const speed = Math.min(Math.hypot(mouse.vx, mouse.vy), 3000);
      disturb(event.clientX, event.clientY, Math.sign(mouse.vy || 1) * speed * 0.035, 50);
      if (speed > 250 && now - lastRipple > 160) {
        lastRipple = now;
        addRipple(event.clientX, event.clientY, Math.min(speed / 1500, 0.7));
      }
    }
    mouse.x = event.clientX;
    mouse.y = event.clientY;
    mouse.last = now;
    mouse.active = true;
  }, { passive: true });
  window.addEventListener('pointerdown', event => {
    if (reducedMotion.matches) return;
    disturb(event.clientX, event.clientY, 140, 80);
    addRipple(event.clientX, event.clientY, 1);
  }, { passive: true });
  document.addEventListener('mouseout', event => { if (!event.relatedTarget) mouse.active = false; });
  window.addEventListener('resize', () => { resize(); if (reducedMotion.matches) draw(); });
  document.addEventListener('visibilitychange', start);
  reducedMotion.addEventListener('change', start);

  resize();
  start();
})();
