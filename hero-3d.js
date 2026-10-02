import * as THREE from 'https://cdn.jsdelivr.net/npm/three@0.180.0/build/three.module.js';

const container = document.getElementById('heroScene');
if (!container) throw new Error('Hero scene container not found');

const fallback = container.querySelector('.hero-scene-fallback');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

const clamp = (value, min = 0, max = 1) => Math.min(Math.max(value, min), max);
const lerp = (a, b, t) => a + (b - a) * t;
const easeOutCubic = t => 1 - Math.pow(1 - t, 3);
const easeOutBack = t => {
  const c1 = 1.25;
  const c3 = c1 + 1;
  return 1 + c3 * Math.pow(t - 1, 3) + c1 * Math.pow(t - 1, 2);
};

// critically-damped-ish spring: smooth follow with a touch of overshoot
const createSpring = (value, stiffness = 42, damping = 10.5) => ({ value, target: value, velocity: 0, stiffness, damping });
const stepSpring = (spring, dt) => {
  const force = (spring.target - spring.value) * spring.stiffness - spring.velocity * spring.damping;
  spring.velocity += force * dt;
  spring.value += spring.velocity * dt;
  return spring.value;
};

const makeGlowTexture = (inner, outer) => {
  const canvas = document.createElement('canvas');
  canvas.width = canvas.height = 128;
  const ctx = canvas.getContext('2d');
  const gradient = ctx.createRadialGradient(64, 64, 0, 64, 64, 64);
  gradient.addColorStop(0, inner);
  gradient.addColorStop(1, outer);
  ctx.fillStyle = gradient;
  ctx.fillRect(0, 0, 128, 128);
  const texture = new THREE.CanvasTexture(canvas);
  texture.colorSpace = THREE.SRGBColorSpace;
  return texture;
};

const makeTagTexture = (label, color) => {
  const canvas = document.createElement('canvas');
  canvas.width = 256;
  canvas.height = 128;
  const ctx = canvas.getContext('2d');
  ctx.fillStyle = 'rgba(17,25,28,0.82)';
  ctx.strokeStyle = color;
  ctx.lineWidth = 4;
  ctx.beginPath();
  ctx.roundRect(8, 24, 240, 80, 40);
  ctx.fill();
  ctx.stroke();
  ctx.fillStyle = color;
  ctx.font = 'bold 44px monospace';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText(label, 128, 66);
  const texture = new THREE.CanvasTexture(canvas);
  texture.colorSpace = THREE.SRGBColorSpace;
  return texture;
};

try {
  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(46, 1, 0.1, 60);
  const cameraBase = new THREE.Vector3(0.45, 7.2, 11.8);
  const lookTarget = new THREE.Vector3(0, 0.9, 0);
  camera.position.copy(cameraBase);
  camera.lookAt(lookTarget);

  const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true, powerPreference: 'high-performance' });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.75));
  renderer.outputColorSpace = THREE.SRGBColorSpace;
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure = 1.15;
  renderer.setClearColor(0x000000, 0);
  renderer.domElement.setAttribute('aria-hidden', 'true');
  container.append(renderer.domElement);
  fallback.hidden = true;

  /* ---------- lights ---------- */
  scene.add(new THREE.HemisphereLight(0xb5fff0, 0x071012, 1.6));
  const keyLight = new THREE.DirectionalLight(0xffffff, 3);
  keyLight.position.set(-3, 6, 5);
  scene.add(keyLight);
  const mintLight = new THREE.PointLight(0x5ce1b5, 18, 12);
  mintLight.position.set(-4, 2, 1);
  scene.add(mintLight);
  const limeLight = new THREE.PointLight(0xb7ff35, 12, 10);
  limeLight.position.set(4, 2, -2);
  scene.add(limeLight);
  const coralLight = new THREE.PointLight(0xff806d, 0, 9);
  coralLight.position.set(3, 3.5, 3);
  scene.add(coralLight);

  /* ---------- laptop ---------- */
  const rig = new THREE.Group();
  scene.add(rig);
  const laptop = new THREE.Group();
  rig.add(laptop);

  const chassisMaterial = new THREE.MeshStandardMaterial({ color: 0x405256, metalness: 0.75, roughness: 0.28 });
  const deckMaterial = new THREE.MeshStandardMaterial({ color: 0x52676a, metalness: 0.62, roughness: 0.34 });
  const keyMaterial = new THREE.MeshStandardMaterial({ color: 0xffffff, emissive: 0x12302e, emissiveIntensity: 0.5, metalness: 0.35, roughness: 0.42 });
  const spaceMaterial = new THREE.MeshStandardMaterial({ color: 0x829496, emissive: 0x12302e, emissiveIntensity: 0.5, metalness: 0.35, roughness: 0.42 });
  const accentMaterial = new THREE.MeshStandardMaterial({ color: 0xb7ff35, emissive: 0x7ab21a, emissiveIntensity: 0.9, metalness: 0.3, roughness: 0.28 });

  const base = new THREE.Mesh(new THREE.BoxGeometry(4.6, 0.16, 2.65), chassisMaterial);
  base.position.set(0, 0.06, 0.25);
  laptop.add(base);

  const deck = new THREE.Mesh(new THREE.BoxGeometry(4.48, 0.035, 2.53), deckMaterial);
  deck.position.set(0, 0.145, 0.25);
  laptop.add(deck);

  const KEY_COUNT = 56;
  const keyboardKeys = new THREE.InstancedMesh(new THREE.BoxGeometry(0.19, 0.035, 0.14), keyMaterial, KEY_COUNT);
  const keyTransform = new THREE.Object3D();
  const keyBaseColor = new THREE.Color(0x829496);
  const keyGlowColor = new THREE.Color(0xb7ff35);
  const keyColor = new THREE.Color();
  const keyGlow = new Float32Array(KEY_COUNT);
  for (let row = 0, index = 0; row < 4; row += 1) {
    for (let column = 0; column < 14; column += 1, index += 1) {
      keyTransform.position.set(-1.68 + column * 0.25, 0.18, -0.48 + row * 0.21);
      keyTransform.updateMatrix();
      keyboardKeys.setMatrixAt(index, keyTransform.matrix);
      keyboardKeys.setColorAt(index, keyBaseColor);
    }
  }
  keyboardKeys.instanceMatrix.needsUpdate = true;
  laptop.add(keyboardKeys);

  const spacebar = new THREE.Mesh(new THREE.BoxGeometry(0.9, 0.036, 0.14), spaceMaterial);
  spacebar.position.set(0, 0.18, 0.39);
  laptop.add(spacebar);

  const trackpadBorder = new THREE.Mesh(
    new THREE.BoxGeometry(1.18, 0.018, 0.64),
    new THREE.MeshStandardMaterial({ color: 0x5ce1b5, metalness: 0.5, roughness: 0.4 })
  );
  trackpadBorder.position.set(0, 0.17, 1.13);
  laptop.add(trackpadBorder);

  const trackpad = new THREE.Mesh(
    new THREE.BoxGeometry(1.12, 0.022, 0.58),
    new THREE.MeshStandardMaterial({ color: 0x1a282b, metalness: 0.56, roughness: 0.38 })
  );
  trackpad.position.set(0, 0.185, 1.13);
  laptop.add(trackpad);

  const frontAccent = new THREE.Mesh(new THREE.BoxGeometry(2.8, 0.018, 0.035), accentMaterial);
  frontAccent.position.set(0, 0.12, 1.57);
  laptop.add(frontAccent);

  const powerIndicator = new THREE.Mesh(new THREE.SphereGeometry(0.035, 16, 12), accentMaterial);
  powerIndicator.position.set(2.05, 0.18, 1.34);
  laptop.add(powerIndicator);

  const hinge = new THREE.Mesh(
    new THREE.CylinderGeometry(0.055, 0.055, 4.15, 20),
    new THREE.MeshStandardMaterial({ color: 0x647879, metalness: 0.84, roughness: 0.25 })
  );
  hinge.rotation.z = Math.PI / 2;
  hinge.position.set(0, 0.2, -1.04);
  laptop.add(hinge);

  const LID_OPEN = -0.075;
  const LID_CLOSED = 1.5;
  const screenRig = new THREE.Group();
  screenRig.position.set(0, 0.16, -1.04);
  screenRig.rotation.x = LID_CLOSED;
  laptop.add(screenRig);

  const screenFrame = new THREE.Mesh(new THREE.BoxGeometry(4.38, 2.92, 0.15), chassisMaterial);
  screenFrame.position.y = 1.43;
  screenRig.add(screenFrame);

  const lidLogo = new THREE.Mesh(new THREE.CircleGeometry(0.22, 32), accentMaterial);
  lidLogo.position.set(0, 1.43, -0.077);
  lidLogo.rotation.y = Math.PI;
  screenRig.add(lidLogo);

  /* ---------- animated screen ---------- */
  const screenCanvas = document.createElement('canvas');
  screenCanvas.width = 1024;
  screenCanvas.height = 680;
  const ctx = screenCanvas.getContext('2d');

  const codeLines = [
    ['const portfolio = {', '#5ce1b5'],
    ['  name: "Jamar Carty",', '#b7ff35'],
    ['  studies: "BTS SIO",', '#edf5f2'],
    ['  option: "SLAM",', '#ff806d'],
    ['  skills: [', '#5ce1b5'],
    ['    "PHP", "JavaScript",', '#edf5f2'],
    ['    "React Native",', '#edf5f2'],
    ['    "Three.js"', '#ffd166'],
    ['  ],', '#5ce1b5'],
    ['  next: "Alternance"', '#b7ff35'],
    ['};', '#5ce1b5'],
    ['', '#edf5f2'],
    ['export default portfolio;', '#ff806d']
  ];
  const totalChars = codeLines.reduce((sum, [code]) => sum + code.length + 1, 0);

  const drawScreen = (typed, cursorOn, status) => {
    ctx.fillStyle = '#081113';
    ctx.fillRect(0, 0, 1024, 680);
    const glow = ctx.createRadialGradient(700, 200, 0, 700, 200, 620);
    glow.addColorStop(0, 'rgba(92,225,181,0.08)');
    glow.addColorStop(1, 'rgba(92,225,181,0)');
    ctx.fillStyle = glow;
    ctx.fillRect(0, 0, 1024, 680);

    ctx.fillStyle = '#142124';
    ctx.fillRect(0, 0, 1024, 58);
    [['#ff806d', 27], ['#ffd166', 53], ['#5ce1b5', 79]].forEach(([color, x]) => {
      ctx.fillStyle = color;
      ctx.beginPath();
      ctx.arc(x, 29, 8, 0, Math.PI * 2);
      ctx.fill();
    });
    ctx.fillStyle = '#a0b2b1';
    ctx.font = '18px monospace';
    ctx.fillText('jamar-carty.dev  /  portfolio.js', 112, 36);
    ctx.fillStyle = '#101a1c';
    ctx.fillRect(0, 58, 86, 572);

    ctx.font = '22px monospace';
    let remaining = typed;
    let cursorX = 160;
    let cursorY = 112;
    codeLines.forEach(([code, color], index) => {
      const y = 112 + index * 39;
      ctx.fillStyle = '#607477';
      ctx.fillText(String(index + 1).padStart(2, '0'), 108, y);
      if (remaining <= 0) return;
      const visible = code.slice(0, remaining);
      ctx.fillStyle = color;
      ctx.fillText(visible, 160, y);
      cursorX = 160 + ctx.measureText(visible).width;
      cursorY = y;
      remaining -= code.length + 1;
      if (remaining > 0 && index < codeLines.length - 1) {
        cursorX = 160;
        cursorY = y + 39;
      }
    });
    if (cursorOn) {
      ctx.fillStyle = '#b7ff35';
      ctx.fillRect(cursorX + 2, cursorY - 20, 12, 26);
    }

    ctx.fillStyle = '#0d1719';
    ctx.fillRect(0, 630, 1024, 50);
    ctx.font = '18px monospace';
    ctx.fillStyle = status ? '#5ce1b5' : '#607477';
    ctx.fillText(status || '● main   UTF-8   JavaScript', 24, 662);
    screenTexture.needsUpdate = true;
  };

  const screenTexture = new THREE.CanvasTexture(screenCanvas);
  screenTexture.colorSpace = THREE.SRGBColorSpace;
  screenTexture.anisotropy = renderer.capabilities.getMaxAnisotropy();
  const screenMaterial = new THREE.MeshBasicMaterial({ map: screenTexture, toneMapped: false, color: 0x000000 });
  const screen = new THREE.Mesh(new THREE.PlaneGeometry(4.12, 2.66), screenMaterial);
  screen.position.set(0, 1.43, 0.081);
  screenRig.add(screen);

  const screenLight = new THREE.PointLight(0x5ce1b5, 0, 6);
  screenLight.position.set(0, 1.4, 1.2);
  screenRig.add(screenLight);

  const cameraDot = new THREE.Mesh(new THREE.SphereGeometry(0.025, 12, 8), new THREE.MeshBasicMaterial({ color: 0x5ce1b5 }));
  cameraDot.position.set(0, 2.88, 0.081);
  screenRig.add(cameraDot);

  /* ---------- ground glow ---------- */
  const groundGlow = new THREE.Mesh(
    new THREE.PlaneGeometry(9, 5.2),
    new THREE.MeshBasicMaterial({
      map: makeGlowTexture('rgba(183,255,53,0.55)', 'rgba(183,255,53,0)'),
      transparent: true, depthWrite: false, blending: THREE.AdditiveBlending, opacity: 0.35
    })
  );
  groundGlow.rotation.x = -Math.PI / 2;
  groundGlow.position.set(0, -0.35, 0.3);
  scene.add(groundGlow);

  /* ---------- particles ---------- */
  const PARTICLE_COUNT = window.innerWidth < 700 ? 140 : 260;
  const particleGeometry = new THREE.BufferGeometry();
  const positions = new Float32Array(PARTICLE_COUNT * 3);
  const colors = new Float32Array(PARTICLE_COUNT * 3);
  const palette = [new THREE.Color(0xb7ff35), new THREE.Color(0x5ce1b5), new THREE.Color(0xff806d)];
  for (let i = 0; i < PARTICLE_COUNT; i += 1) {
    const radius = 3.6 + Math.random() * 3.4;
    const angle = Math.random() * Math.PI * 2;
    positions[i * 3] = Math.cos(angle) * radius;
    positions[i * 3 + 1] = -0.8 + Math.random() * 5.2;
    positions[i * 3 + 2] = Math.sin(angle) * radius * 0.7;
    const color = palette[Math.random() < 0.55 ? 0 : Math.random() < 0.8 ? 1 : 2];
    colors.set([color.r, color.g, color.b], i * 3);
  }
  particleGeometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
  particleGeometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));
  const particles = new THREE.Points(particleGeometry, new THREE.PointsMaterial({
    size: 0.11, vertexColors: true, transparent: true, opacity: 0.85, depthWrite: false,
    blending: THREE.AdditiveBlending, map: makeGlowTexture('rgba(255,255,255,1)', 'rgba(255,255,255,0)')
  }));
  scene.add(particles);

  /* ---------- floating tags & shapes ---------- */
  const floaters = [];
  [['</>', '#b7ff35'], ['API', '#5ce1b5'], ['PHP', '#ffd166'], ['{ }', '#ff806d'], ['SQL', '#5ce1b5'], ['JS', '#b7ff35']].forEach(([label, color], index, list) => {
    const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: makeTagTexture(label, color), transparent: true, depthWrite: false, opacity: 0 }));
    sprite.scale.set(1.1, 0.55, 1);
    scene.add(sprite);
    floaters.push({
      object: sprite,
      angle: (index / list.length) * Math.PI * 2,
      radius: 4.1 + (index % 2) * 0.6,
      height: 1.1 + (index % 3) * 1.05,
      speed: 0.12 + (index % 3) * 0.03,
      phase: index * 1.7
    });
  });

  const shapes = [];
  [[new THREE.OctahedronGeometry(0.32), 0xb7ff35, [-3.3, 3.6, -1.2]], [new THREE.IcosahedronGeometry(0.26), 0x5ce1b5, [3.4, 3.9, -0.6]], [new THREE.TorusGeometry(0.24, 0.07, 12, 32), 0xff806d, [2.9, 0.5, 2.1]]]
    .forEach(([geometry, color, [x, y, z]], index) => {
      const mesh = new THREE.Mesh(geometry, new THREE.MeshStandardMaterial({ color, emissive: color, emissiveIntensity: 0.55, metalness: 0.4, roughness: 0.3, wireframe: index !== 2 }));
      mesh.position.set(x, y, z);
      mesh.scale.setScalar(0.001);
      scene.add(mesh);
      shapes.push({ mesh, base: new THREE.Vector3(x, y, z), phase: index * 2.1 });
    });

  /* ---------- interaction ---------- */
  const tiltX = createSpring(0);
  const tiltY = createSpring(0);
  const hover = createSpring(0, 30, 9);
  const pointer = { x: 0, y: 0, inside: false };
  const drag = { active: false, lastX: 0, offset: 0, velocity: 0 };

  window.addEventListener('pointermove', event => {
    if (event.pointerType !== 'mouse' || reducedMotion.matches) return;
    const bounds = container.getBoundingClientRect();
    pointer.x = clamp((event.clientX - (bounds.left + bounds.width / 2)) / (window.innerWidth / 2), -1, 1);
    pointer.y = clamp((event.clientY - (bounds.top + bounds.height / 2)) / (window.innerHeight / 2), -1, 1);
  }, { passive: true });
  document.addEventListener('mouseout', event => { if (!event.relatedTarget) { pointer.x = 0; pointer.y = 0; } });
  container.addEventListener('pointerenter', () => { pointer.inside = true; });
  container.addEventListener('pointerleave', () => { pointer.inside = false; });

  container.addEventListener('pointerdown', event => {
    if (reducedMotion.matches) return;
    drag.active = true;
    drag.lastX = event.clientX;
    drag.velocity = 0;
    container.setPointerCapture(event.pointerId);
    container.classList.add('is-dragging');
  });
  container.addEventListener('pointermove', event => {
    if (!drag.active) return;
    const dx = (event.clientX - drag.lastX) / container.clientWidth;
    drag.lastX = event.clientX;
    drag.offset += dx * Math.PI * 1.6;
    drag.velocity = dx * Math.PI * 1.6 * 60;
  });
  const endDrag = () => {
    drag.active = false;
    container.classList.remove('is-dragging');
  };
  container.addEventListener('pointerup', endDrag);
  container.addEventListener('pointercancel', endDrag);

  /* ---------- sizing & visibility ---------- */
  const resize = () => {
    const bounds = container.getBoundingClientRect();
    if (!bounds.width || !bounds.height) return;
    renderer.setSize(bounds.width, bounds.height, false);
    camera.aspect = bounds.width / bounds.height;
    camera.fov = camera.aspect < 0.9 ? 54 : 46;
    camera.updateProjectionMatrix();
    renderer.render(scene, camera);
  };
  new ResizeObserver(resize).observe(container);

  let inView = true;
  new IntersectionObserver(([entry]) => {
    inView = entry.isIntersecting;
    updateAnimation();
  }).observe(container);

  /* ---------- typing state ---------- */
  const typing = { chars: 0, nextAt: 0, holdUntil: 0, status: '' };
  let lastDrawKey = '';
  const pressRandomKey = () => {
    keyGlow[Math.floor(Math.random() * KEY_COUNT)] = 1;
  };

  /* ---------- loop ---------- */
  let startTime = 0;
  let previousTime = 0;
  let animationFrame = 0;

  const render = time => {
    if (!startTime) startTime = time;
    const dt = previousTime ? Math.min((time - previousTime) / 1000, 0.05) : 1 / 60;
    previousTime = time;
    const elapsed = (time - startTime) / 1000;
    const still = reducedMotion.matches;
    const t = still ? 10 : elapsed;

    // intro: rise in, open the lid, power on the screen
    const enter = easeOutCubic(clamp(t / 1.3));
    const lid = easeOutBack(clamp((t - 0.45) / 1.25));
    const power = clamp((t - 1.25) / 0.6);
    screenRig.rotation.x = lerp(LID_CLOSED, LID_OPEN, lid);
    screenMaterial.color.setScalar(power);
    screenLight.intensity = power * 4.5;

    // pointer tilt + hover
    tiltX.target = still ? 0 : pointer.y * 0.16;
    tiltY.target = still ? 0 : pointer.x * 0.32;
    hover.target = pointer.inside || drag.active ? 1 : 0;
    stepSpring(tiltX, dt);
    stepSpring(tiltY, dt);
    stepSpring(hover, dt);

    // drag spin with inertia, settling back on the nearest full turn
    if (!drag.active) {
      if (Math.abs(drag.velocity) > 0.25) {
        drag.offset += drag.velocity * dt;
        drag.velocity *= Math.exp(-2.6 * dt);
      } else {
        const rest = Math.round(drag.offset / (Math.PI * 2)) * Math.PI * 2;
        drag.velocity = 0;
        drag.offset += (rest - drag.offset) * (1 - Math.exp(-4 * dt));
      }
    }

    // scroll: tilt away as the hero leaves the viewport
    const scrollProgress = clamp(window.scrollY / Math.max(container.offsetHeight, 1));

    const idle = still ? 0 : t;
    rig.position.y = lerp(-1.4, 0, enter) + Math.sin(idle * 0.9) * 0.08 + hover.value * 0.18 - scrollProgress * 0.8;
    rig.rotation.x = -0.035 + tiltX.value + Math.sin(idle * 0.45) * 0.015 + scrollProgress * 0.35;
    rig.rotation.y = -0.16 + lerp(-0.9, 0, enter) + tiltY.value + drag.offset + Math.sin(idle * 0.36) * 0.04;
    rig.rotation.z = Math.sin(idle * 0.5) * 0.012 - tiltY.value * 0.08;
    rig.scale.setScalar(1.18 * lerp(0.82, 1, enter) * (1 + hover.value * 0.03));

    camera.position.set(cameraBase.x + tiltY.value * 1.2, cameraBase.y - tiltX.value * 1.5, cameraBase.z);
    camera.lookAt(lookTarget);

    groundGlow.material.opacity = (0.22 + hover.value * 0.18 + Math.sin(idle * 1.6) * 0.04) * enter;
    groundGlow.scale.setScalar(1 - (rig.position.y + 0.2) * 0.12);
    coralLight.intensity = hover.value * 8;
    accentMaterial.emissiveIntensity = 0.7 + Math.sin(idle * 2.2) * 0.3;

    particles.rotation.y = idle * 0.035;
    particles.position.y = Math.sin(idle * 0.4) * 0.15;
    particles.material.opacity = 0.85 * enter;

    floaters.forEach(floater => {
      const angle = floater.angle + idle * floater.speed;
      floater.object.position.set(
        Math.cos(angle) * floater.radius,
        floater.height + Math.sin(idle * 1.1 + floater.phase) * 0.22,
        Math.sin(angle) * floater.radius * 0.55
      );
      // fade tags that pass behind the laptop
      const front = clamp((floater.object.position.z + 1.6) / 2.4);
      floater.object.material.opacity = clamp((t - 1.6) / 0.8) * lerp(0.25, 0.95, front);
    });

    shapes.forEach(({ mesh, base: origin, phase }) => {
      mesh.scale.setScalar(Math.max(easeOutBack(clamp((t - 1.4) / 0.9)), 0.001));
      mesh.rotation.x = idle * 0.6 + phase;
      mesh.rotation.y = idle * 0.8 + phase;
      mesh.position.set(origin.x + tiltY.value * 0.8, origin.y + Math.sin(idle * 0.9 + phase) * 0.25, origin.z);
    });

    // typing on screen + keyboard flashes
    if (still) {
      typing.chars = totalChars;
    } else if (t > 1.7) {
      if (typing.chars < totalChars) {
        if (elapsed >= typing.nextAt) {
          typing.chars += 1;
          typing.nextAt = elapsed + 0.028 + Math.random() * 0.06;
          pressRandomKey();
          if (Math.random() < 0.3) pressRandomKey();
        }
      } else if (!typing.holdUntil) {
        typing.status = '▶ npm run dev   ✓ ready in 312 ms';
        typing.holdUntil = elapsed + 4.2;
      } else if (elapsed > typing.holdUntil) {
        typing.chars = 0;
        typing.holdUntil = 0;
        typing.status = '';
        typing.nextAt = elapsed + 0.6;
      }
    }
    const cursorOn = still || Math.floor(elapsed / 0.53) % 2 === 0;
    const drawKey = `${typing.chars}|${cursorOn}|${typing.status}`;
    if (drawKey !== lastDrawKey) {
      lastDrawKey = drawKey;
      drawScreen(typing.chars, cursorOn, typing.status);
    }

    const decay = Math.exp(-7 * dt);
    for (let i = 0; i < KEY_COUNT; i += 1) {
      keyGlow[i] *= decay;
      keyboardKeys.setColorAt(i, keyColor.copy(keyBaseColor).lerp(keyGlowColor, keyGlow[i]));
    }
    keyboardKeys.instanceColor.needsUpdate = true;

    renderer.render(scene, camera);
    animationFrame = still || document.hidden || !inView ? 0 : requestAnimationFrame(render);
  };

  function updateAnimation() {
    if (animationFrame) cancelAnimationFrame(animationFrame);
    previousTime = 0;
    animationFrame = requestAnimationFrame(render);
  }

  reducedMotion.addEventListener('change', updateAnimation);
  document.addEventListener('visibilitychange', updateAnimation);
  resize();
  updateAnimation();
} catch (error) {
  console.error('Impossible de créer la scène 3D du hero.', error);
  fallback.hidden = false;
}
