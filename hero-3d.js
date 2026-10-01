import * as THREE from 'https://cdn.jsdelivr.net/npm/three@0.180.0/build/three.module.js';

const container = document.getElementById('heroScene');
if (!container) throw new Error('Hero scene container not found');

const fallback = container.querySelector('.hero-scene-fallback');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

try {
  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(46, 1, 0.1, 50);
  camera.position.set(0.45, 7.2, 11.8);
  camera.lookAt(0, 0.6, 0);

  const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true, powerPreference: 'high-performance' });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
  renderer.outputColorSpace = THREE.SRGBColorSpace;
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure = 1.15;
  renderer.setClearColor(0x000000, 0);
  renderer.domElement.setAttribute('aria-hidden', 'true');
  container.append(renderer.domElement);
  fallback.hidden = true;

  scene.add(new THREE.HemisphereLight(0xb5fff0, 0x071012, 1.8));

  const keyLight = new THREE.DirectionalLight(0xffffff, 3.2);
  keyLight.position.set(-3, 6, 5);
  scene.add(keyLight);

  const mintLight = new THREE.PointLight(0x5ce1b5, 18, 12);
  mintLight.position.set(-4, 2, 1);
  scene.add(mintLight);

  const limeLight = new THREE.PointLight(0xb7ff35, 12, 10);
  limeLight.position.set(4, 2, -2);
  scene.add(limeLight);

  const laptop = new THREE.Group();
  laptop.rotation.set(-0.035, -0.16, 0);
  laptop.scale.setScalar(1.18);
  scene.add(laptop);

  const chassisMaterial = new THREE.MeshStandardMaterial({ color: 0x405256, metalness: 0.72, roughness: 0.3 });
  const deckMaterial = new THREE.MeshStandardMaterial({ color: 0x52676a, metalness: 0.62, roughness: 0.34 });
  const keyMaterial = new THREE.MeshStandardMaterial({ color: 0x829496, emissive: 0x12302e, emissiveIntensity: 0.5, metalness: 0.35, roughness: 0.42 });
  const accentMaterial = new THREE.MeshStandardMaterial({ color: 0xb7ff35, emissive: 0x527a08, emissiveIntensity: 0.7, metalness: 0.3, roughness: 0.28 });

  const base = new THREE.Mesh(new THREE.BoxGeometry(4.6, 0.16, 2.65), chassisMaterial);
  base.position.set(0, 0.06, 0.25);
  laptop.add(base);

  const deck = new THREE.Mesh(new THREE.BoxGeometry(4.48, 0.035, 2.53), deckMaterial);
  deck.position.set(0, 0.145, 0.25);
  laptop.add(deck);

  const keyboardKeys = new THREE.InstancedMesh(new THREE.BoxGeometry(0.19, 0.035, 0.14), keyMaterial, 56);
  const keyTransform = new THREE.Object3D();
  let keyIndex = 0;
  for (let row = 0; row < 4; row += 1) {
    for (let column = 0; column < 14; column += 1) {
      keyTransform.position.set(-1.68 + column * 0.25, 0.18, -0.48 + row * 0.21);
      keyTransform.updateMatrix();
      keyboardKeys.setMatrixAt(keyIndex, keyTransform.matrix);
      keyIndex += 1;
    }
  }
  keyboardKeys.instanceMatrix.needsUpdate = true;
  laptop.add(keyboardKeys);

  const spacebar = new THREE.Mesh(new THREE.BoxGeometry(0.9, 0.036, 0.14), keyMaterial);
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

  const screenRig = new THREE.Group();
  screenRig.position.set(0, 0.16, -1.04);
  screenRig.rotation.x = -0.075;
  laptop.add(screenRig);

  const screenFrame = new THREE.Mesh(new THREE.BoxGeometry(4.38, 2.92, 0.15), chassisMaterial);
  screenFrame.position.y = 1.43;
  screenRig.add(screenFrame);

  const screenTextureCanvas = document.createElement('canvas');
  screenTextureCanvas.width = 1024;
  screenTextureCanvas.height = 680;
  const context = screenTextureCanvas.getContext('2d');
  context.fillStyle = '#081113';
  context.fillRect(0, 0, 1024, 680);
  context.fillStyle = '#142124';
  context.fillRect(0, 0, 1024, 58);
  context.fillStyle = '#ff806d';
  context.beginPath();
  context.arc(27, 29, 8, 0, Math.PI * 2);
  context.fill();
  context.fillStyle = '#ffd166';
  context.beginPath();
  context.arc(53, 29, 8, 0, Math.PI * 2);
  context.fill();
  context.fillStyle = '#5ce1b5';
  context.beginPath();
  context.arc(79, 29, 8, 0, Math.PI * 2);
  context.fill();
  context.fillStyle = '#a0b2b1';
  context.font = '18px monospace';
  context.fillText('jamar-carty.dev  /  portfolio.js', 112, 36);
  context.fillStyle = '#101a1c';
  context.fillRect(0, 58, 86, 622);

  const codeLines = [
    ['01', 'const portfolio = {', '#5ce1b5'],
    ['02', '  name: "Jamar Carty",', '#b7ff35'],
    ['03', '  studies: "BTS SIO",', '#edf5f2'],
    ['04', '  option: "SLAM",', '#ff806d'],
    ['05', '  skills: [', '#5ce1b5'],
    ['06', '    "PHP", "JavaScript",', '#edf5f2'],
    ['07', '    "React Native",', '#edf5f2'],
    ['08', '    "Three.js"', '#ffd166'],
    ['09', '  ],', '#5ce1b5'],
    ['10', '  next: "Data science"', '#b7ff35'],
    ['11', '};', '#5ce1b5'],
    ['12', '', '#edf5f2'],
    ['13', 'export default portfolio;', '#ff806d']
  ];

  context.font = '22px monospace';
  codeLines.forEach(([number, code, color], index) => {
    const y = 112 + index * 41;
    context.fillStyle = '#607477';
    context.fillText(number, 108, y);
    context.fillStyle = color;
    context.fillText(code, 160, y);
  });

  const screenTexture = new THREE.CanvasTexture(screenTextureCanvas);
  screenTexture.colorSpace = THREE.SRGBColorSpace;
  screenTexture.anisotropy = renderer.capabilities.getMaxAnisotropy();
  const screen = new THREE.Mesh(
    new THREE.PlaneGeometry(4.12, 2.66),
    new THREE.MeshBasicMaterial({ map: screenTexture, toneMapped: false })
  );
  screen.position.set(0, 1.43, 0.081);
  screenRig.add(screen);

  const cameraDot = new THREE.Mesh(
    new THREE.SphereGeometry(0.025, 12, 8),
    new THREE.MeshBasicMaterial({ color: 0x5ce1b5 })
  );
  cameraDot.position.set(0, 2.88, 0.081);
  screenRig.add(cameraDot);

  const pointer = { x: 0, y: 0 };
  const initialRotation = { x: -0.035, y: -0.16 };

  container.addEventListener('pointermove', event => {
    if (event.pointerType !== 'mouse' || reducedMotion.matches) return;
    const bounds = container.getBoundingClientRect();
    pointer.x = ((event.clientX - bounds.left) / bounds.width - 0.5) * 2;
    pointer.y = ((event.clientY - bounds.top) / bounds.height - 0.5) * 2;
  });
  container.addEventListener('pointerleave', () => {
    pointer.x = 0;
    pointer.y = 0;
  });

  const resize = () => {
    const bounds = container.getBoundingClientRect();
    if (!bounds.width || !bounds.height) return;
    renderer.setSize(bounds.width, bounds.height, false);
    camera.aspect = bounds.width / bounds.height;
    camera.updateProjectionMatrix();
    renderer.render(scene, camera);
  };

  const resizeObserver = new ResizeObserver(resize);
  resizeObserver.observe(container);
  window.addEventListener('resize', resize);

  let animationFrame = 0;
  let previousFrameTime = 0;
  const render = time => {
    const delta = previousFrameTime ? Math.min((time - previousFrameTime) / 1000, 0.05) : 1 / 60;
    previousFrameTime = time;

    if (!reducedMotion.matches) {
      const seconds = time * 0.001;
      const targetX = initialRotation.x + pointer.y * 0.055 + Math.sin(seconds * 0.45) * 0.012;
      const targetY = initialRotation.y + pointer.x * 0.12 + Math.sin(seconds * 0.36) * 0.025;
      const smoothing = 1 - Math.exp(-3.5 * delta);
      laptop.rotation.x += (targetX - laptop.rotation.x) * smoothing;
      laptop.rotation.y += (targetY - laptop.rotation.y) * smoothing;
      laptop.position.y = Math.sin(seconds * 0.55) * 0.035;
    }

    renderer.render(scene, camera);
    animationFrame = reducedMotion.matches || document.hidden ? 0 : requestAnimationFrame(render);
  };

  const updateAnimation = () => {
    if (animationFrame) cancelAnimationFrame(animationFrame);
    animationFrame = requestAnimationFrame(render);
  };

  reducedMotion.addEventListener('change', updateAnimation);
  document.addEventListener('visibilitychange', updateAnimation);
  resize();
  updateAnimation();
} catch (error) {
  console.error('Impossible de créer la scène 3D du hero.', error);
  fallback.hidden = false;
}