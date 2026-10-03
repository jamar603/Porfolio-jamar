// animated site background: flowing satin rendered with a WebGL2 shader —
// six wide ribbons that bulge and twist, shaded like glossy fabric (navy body, teal sheen, soft specular),
// casting soft shadows on the layers beneath and sinking into darkness at the edges.
// Each page section has its own cloth form; scrolling morphs between them and stirs the folds.
// The cursor moves the light across the satin.
(() => {
  const canvas = document.createElement('canvas');
  canvas.className = 'bg-canvas';
  canvas.setAttribute('aria-hidden', 'true');
  document.body.prepend(canvas);

  const gl = canvas.getContext('webgl2', { alpha: true, premultipliedAlpha: true, antialias: false, depth: false, stencil: false });
  if (!gl) { canvas.remove(); return; } // the CSS background stays as a fallback

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  const VERTEX = `#version 300 es
void main() {
  vec2 p = vec2((gl_VertexID << 1) & 2, gl_VertexID & 2);
  gl_Position = vec4(p * 2.0 - 1.0, 0.0, 1.0);
}`;

  const FRAGMENT = `#version 300 es
precision highp float;
uniform vec2 uRes;    // drawing buffer size
uniform float uTime;
uniform float uRoll;  // smoothed scroll, slides the folds along the ribbons
uniform vec2 uLight;  // smoothed cursor, -1..1, steers the light
uniform float uFade;
uniform float uSwirl; // scroll speed, stirs the cloth
// current form, blended between presets as the page scrolls
uniform float uAngle;  // main sweep direction
uniform float uSpread; // distance between ribbons
uniform float uAmp;    // wave height
uniform float uFreq;   // wave length
uniform float uWidth;  // ribbon width
uniform float uTwist;  // how far ribbons roll over
uniform vec2 uSpot;    // where the cloth sits in the frame
uniform float uHue;    // 0 teal, 1 the site's blue
out vec4 outColor;

float hash(vec2 p) {
  vec3 p3 = fract(vec3(p.xyx) * 0.1031);
  p3 += dot(p3, p3.yzx + 33.33);
  return fract((p3.x + p3.y) * p3.z);
}

// one satin ribbon: returns colour in rgb, coverage in a, and writes a soft shadow factor
vec4 ribbon(vec2 p, float i, float t, vec3 L, float px, out float shadow) {
  float ang = uAngle + 0.22 * sin(i * 2.1);               // each ribbon sweeps at its own diagonal
  vec2 q = mat2(cos(ang), -sin(ang), sin(ang), cos(ang)) * p;
  float x = q.x + uRoll * (0.6 + i * 0.12);               // scrolling slides the folds along, never off screen
  float centre = uAmp * sin(x * uFreq + t * 0.55 + i * 1.3)
               + uAmp * 0.42 * sin(x * uFreq * 2.2 - t * 0.38 + i * 2.7 + uSwirl * 2.0)
               + (i - 2.5) * uSpread;
  float halfW = uWidth * (1.0 + 0.42 * sin(x * 1.25 + t * 0.47 + i * 0.9));
  float d = (q.y - centre) / halfW;                        // -1..1 across the ribbon
  shadow = 1.0 - 0.6 * exp(-max(abs(d) - 1.0, 0.0) * halfW / 0.09);
  if (abs(d) > 1.0) return vec4(0.0);

  // cross-section bulges like a cylinder, then twists around the ribbon's long axis
  float tw = (uTwist + uSwirl) * sin(x * 0.75 + t * 0.33 + i * 1.9);
  float ny = d;
  float nz = sqrt(max(1.0 - d * d, 0.0));
  float ry = ny * cos(tw) - nz * sin(tw);
  float rz = ny * sin(tw) + nz * cos(tw);
  float back = step(rz, 0.0);                              // seeing the reverse side of the cloth
  rz = abs(rz);
  vec2 nxy = mat2(cos(-ang), -sin(-ang), sin(-ang), cos(-ang)) * vec2(0.0, ry);
  vec3 n = normalize(vec3(nxy, rz));

  vec3 H = normalize(L + vec3(0.0, 0.0, 1.0));
  float diffuse = smoothstep(-0.3, 0.8, dot(n, L));
  float nh = max(dot(n, H), 0.0);

  vec3 black = vec3(0.02, 0.03, 0.06);
  vec3 navy = vec3(0.1, 0.16, 0.36);
  vec3 teal = mix(vec3(0.12, 0.48, 0.62), vec3(0.15, 0.3, 0.62), uHue);
  vec3 glint = mix(vec3(0.55, 0.86, 0.95), vec3(0.55, 0.72, 0.95), uHue);
  vec3 col = mix(black, mix(navy, teal, smoothstep(0.3, 1.0, diffuse) * (1.0 - back * 0.6)), diffuse);
  col += teal * pow(nh, 6.0) * 0.5 + glint * pow(nh, 50.0) * 0.75;
  col *= mix(0.5, 1.0, i / 5.0);                           // ribbons further back sit in shadow

  float edge = 1.0 - smoothstep(1.0 - px / halfW * 1.5, 1.0, abs(d));
  return vec4(col, edge);
}

void main() {
  vec2 uv = gl_FragCoord.xy / uRes;
  float minRes = min(uRes.x, uRes.y);
  float px = 2.6 / minRes;
  vec2 p = (gl_FragCoord.xy - uRes * 0.5) / minRes * 2.6;
  float t = uTime * 0.16;
  vec3 L = normalize(vec3(-0.75 + uLight.x * 0.5, 0.65 + uLight.y * 0.4, 0.55)); // key light upper left, follows the cursor

  vec3 col = vec3(0.02, 0.03, 0.055);
  for (int k = 0; k < 6; k++) {                            // back to front
    float shadow;
    vec4 r = ribbon(p, float(k), t, L, px, shadow);
    col *= shadow;                                         // each ribbon darkens what lies just under its edges
    col = mix(col, r.rgb, r.a);
  }

  // the cloth melts into darkness toward the corners, like a studio backdrop
  vec2 c = uv - uSpot;
  float spot = exp(-dot(c * vec2(1.1, 1.0), c * vec2(1.1, 1.0)) * 1.8);
  col = mix(vec3(0.02, 0.03, 0.055), col, 0.3 + 0.7 * spot);

  col += (hash(gl_FragCoord.xy + fract(uTime * 7.0)) - 0.5) / 255.0; // dither away banding
  outColor = vec4(max(col, 0.0), 1.0) * uFade;
}`;

  // one cloth form per page section; the background morphs between them while scrolling
  const FORMS = [
    { angle: -0.55, spread: 0.3, amp: 0.38, freq: 0.85, width: 0.28, twist: 1.3, spot: [0.45, 0.55], hue: 0 },   // diagonal sweep
    { angle: -0.08, spread: 0.2, amp: 0.24, freq: 0.6, width: 0.36, twist: 0.7, spot: [0.7, 0.5], hue: 0.6 },    // calm horizontal swell
    { angle: -1.15, spread: 0.34, amp: 0.5, freq: 1.05, width: 0.22, twist: 1.9, spot: [0.3, 0.5], hue: 0.2 },   // steep cascade
    { angle: 0.55, spread: 0.12, amp: 0.55, freq: 1.3, width: 0.26, twist: 2.3, spot: [0.62, 0.42], hue: 0.85 }, // tight twisted braid
    { angle: -0.32, spread: 0.42, amp: 0.3, freq: 0.5, width: 0.46, twist: 0.9, spot: [0.5, 0.6], hue: 0.35 },   // wide drape
  ];
  const KEYS = ['angle', 'spread', 'amp', 'freq', 'width', 'twist', 'hue'];

  const pointer = { x: 0, y: 0, tx: 0, ty: 0 };
  const scroll = { current: window.scrollY, swirl: 0 };
  let anchors = [0]; // page offsets where each form takes over: top of page, then every section
  let anchorsAge = Infinity;
  let form = 0;      // smoothed position in FORMS, fractional while morphing
  let program = null;
  let uniforms = {};
  let width = 0;
  let height = 0;
  let scale = 0.6; // drawing buffer resolution relative to CSS px; smooth cloth survives upscaling
  let frame = 0;
  let clock = 0;
  let previous = 0;
  let fadeIn = 0;
  let slowFrames = 0;

  const damp = (from, to, rate, dt) => to + (from - to) * Math.exp(-rate * dt);
  const easeOutCubic = t => 1 - (1 - t) ** 3;

  const compile = (type, source) => {
    const shader = gl.createShader(type);
    gl.shaderSource(shader, source);
    gl.compileShader(shader);
    if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) throw new Error(gl.getShaderInfoLog(shader));
    return shader;
  };

  const setup = () => {
    program = gl.createProgram();
    gl.attachShader(program, compile(gl.VERTEX_SHADER, VERTEX));
    gl.attachShader(program, compile(gl.FRAGMENT_SHADER, FRAGMENT));
    gl.linkProgram(program);
    if (!gl.getProgramParameter(program, gl.LINK_STATUS)) throw new Error(gl.getProgramInfoLog(program));
    gl.useProgram(program);
    ['uRes', 'uTime', 'uRoll', 'uLight', 'uFade', 'uSwirl', 'uAngle', 'uSpread', 'uAmp', 'uFreq', 'uWidth', 'uTwist', 'uSpot', 'uHue']
      .forEach(name => { uniforms[name] = gl.getUniformLocation(program, name); });
  };

  const sizeBuffer = () => {
    canvas.width = Math.max(1, Math.round(width * scale));
    canvas.height = Math.max(1, Math.round(height * scale));
    gl.viewport(0, 0, canvas.width, canvas.height);
  };

  const resize = () => {
    width = window.innerWidth;
    height = window.innerHeight;
    scale = Math.min(window.devicePixelRatio || 1, 1.5) * (width < 700 ? 0.5 : 0.6);
    sizeBuffer();
  };

  const measureAnchors = () => {
    const sections = [...document.querySelectorAll('section[id]')]; // the hero has no id, it keeps the first form
    anchors = [0, ...sections.map(s => s.getBoundingClientRect().top + window.scrollY)].sort((a, b) => a - b);
    anchorsAge = 0;
  };

  // which form the page is on: the section under the middle of the viewport,
  // morphing into the next one over the last third of each section
  const targetForm = () => {
    const mid = window.scrollY + window.innerHeight * 0.5;
    let k = 0;
    while (k < anchors.length - 1 && anchors[k + 1] <= mid) k += 1;
    const end = anchors[k + 1] ?? document.documentElement.scrollHeight;
    const f = Math.min(1, Math.max(0, (mid - anchors[k]) / Math.max(1, end - anchors[k])));
    const g = f < 0.66 ? 0 : (f - 0.66) / 0.34;
    return k + g * g * (3 - 2 * g);
  };

  const update = dt => {
    clock += dt;
    anchorsAge += dt;
    if (anchorsAge > 1) measureAnchors(); // content (articles, images) can shift sections after load
    const before = scroll.current;
    scroll.current = damp(scroll.current, window.scrollY, 4, dt);
    scroll.swirl = damp(scroll.swirl, Math.min(Math.abs(scroll.current - before) / dt / 3000, 0.6), 2.5, dt);
    form = damp(form, targetForm(), 2.2, dt);
    pointer.x = damp(pointer.x, pointer.tx, 1.5, dt);
    pointer.y = damp(pointer.y, pointer.ty, 1.5, dt);
    fadeIn = Math.min(1, fadeIn + dt / 1.6);
  };

  const draw = () => {
    const k = Math.floor(form);
    const a = FORMS[k % FORMS.length];
    const b = FORMS[(k + 1) % FORMS.length];
    const f = form - k;
    const mix = key => a[key] + (b[key] - a[key]) * f;
    gl.uniform2f(uniforms.uRes, canvas.width, canvas.height);
    gl.uniform1f(uniforms.uTime, clock);
    gl.uniform1f(uniforms.uRoll, scroll.current * 0.0006);
    gl.uniform2f(uniforms.uLight, pointer.x, pointer.y);
    gl.uniform1f(uniforms.uFade, easeOutCubic(fadeIn));
    gl.uniform1f(uniforms.uSwirl, scroll.swirl);
    KEYS.forEach(key => gl.uniform1f(uniforms[`u${key[0].toUpperCase()}${key.slice(1)}`], mix(key)));
    gl.uniform2f(uniforms.uSpot, a.spot[0] + (b.spot[0] - a.spot[0]) * f, a.spot[1] + (b.spot[1] - a.spot[1]) * f);
    gl.drawArrays(gl.TRIANGLES, 0, 3);
  };

  // drop the resolution step by step while frames keep running long
  const adapt = elapsed => {
    slowFrames = elapsed > 0.024 ? slowFrames + 1 : Math.max(0, slowFrames - 1);
    if (slowFrames > 45 && scale > 0.3) {
      slowFrames = 0;
      scale *= 0.8;
      sizeBuffer();
    }
  };

  const loop = now => {
    const elapsed = previous ? Math.min((now - previous) / 1000, 0.1) : 1 / 60;
    previous = now;
    update(elapsed);
    draw();
    adapt(elapsed);
    frame = requestAnimationFrame(loop);
  };

  const start = () => {
    cancelAnimationFrame(frame);
    frame = 0;
    previous = 0;
    if (gl.isContextLost()) return;
    if (reducedMotion.matches) {
      fadeIn = 1;
      scroll.current = window.scrollY;
      measureAnchors();
      form = targetForm();
      if (!clock) clock = 10;
      draw();
    } else if (!document.hidden) {
      frame = requestAnimationFrame(loop);
    }
  };

  window.addEventListener('pointermove', event => {
    if (event.pointerType !== 'mouse') return;
    pointer.tx = (event.clientX / width) * 2 - 1;
    pointer.ty = 1 - (event.clientY / height) * 2;
  }, { passive: true });
  window.addEventListener('resize', () => { resize(); if (reducedMotion.matches) draw(); });
  window.addEventListener('scroll', () => { if (reducedMotion.matches) { scroll.current = window.scrollY; form = targetForm(); draw(); } }, { passive: true });
  document.addEventListener('visibilitychange', start);
  reducedMotion.addEventListener('change', start);
  canvas.addEventListener('webglcontextlost', event => { event.preventDefault(); cancelAnimationFrame(frame); });
  canvas.addEventListener('webglcontextrestored', () => { setup(); resize(); start(); });

  try {
    setup();
  } catch (error) {
    console.warn('Satin background disabled:', error);
    canvas.remove();
    return;
  }
  resize();
  measureAnchors();
  form = targetForm(); // a reload halfway down the page starts on that section's form
  start();
})();
