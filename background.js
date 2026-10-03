// animated site background: underwater scene rendered with a WebGL2 shader —
// dispersive caustics, swaying god rays, surface glare, marine snow, rising bubbles,
// and a ripple height field the cursor disturbs. Scrolling the page sinks you deeper.
(() => {
  const canvas = document.createElement('canvas');
  canvas.className = 'bg-canvas';
  canvas.setAttribute('aria-hidden', 'true');
  document.body.prepend(canvas);

  const gl = canvas.getContext('webgl2', { alpha: true, premultipliedAlpha: true, antialias: false, depth: false, stencil: false });
  if (!gl) { canvas.remove(); return; } // the CSS background stays as a fallback

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const STEP = 1 / 60; // fixed simulation step, so motion is identical at 60, 120 or 144 Hz
  const DAMPING = 0.986; // energy kept by the ripple field each step

  const VERTEX = `#version 300 es
void main() {
  vec2 p = vec2((gl_VertexID << 1) & 2, gl_VertexID & 2);
  gl_Position = vec4(p * 2.0 - 1.0, 0.0, 1.0);
}`;

  const FRAGMENT = `#version 300 es
precision highp float;
uniform vec2 uRes;       // drawing buffer size
uniform vec2 uView;      // viewport size in CSS px
uniform float uTime;
uniform float uScroll;   // smoothed scrollY
uniform float uDepth;    // 0 at the top of the page, 1 at the bottom
uniform float uSwell;    // scroll speed, stirs the water
uniform float uFade;
uniform sampler2D uHeight;
uniform vec2 uTexel;
out vec4 outColor;

float hash(vec2 p) {
  vec3 p3 = fract(vec3(p.xyx) * 0.1031);
  p3 += dot(p3, p3.yzx + 33.33);
  return fract((p3.x + p3.y) * p3.z);
}

float noise1(float x) {
  float i = floor(x);
  float f = fract(x);
  return mix(hash(vec2(i, 1.7)), hash(vec2(i + 1.0, 1.7)), f * f * (3.0 - 2.0 * f));
}

// iterated-distortion caustic network: bright lines where refracted light converges
float caustic(vec2 uv, float t) {
  vec2 p = uv * 6.2831853 - 250.0;
  vec2 i = p;
  float c = 1.0;
  for (int n = 0; n < 5; n++) {
    float tt = t * (1.0 - 3.5 / float(n + 1));
    i = p + vec2(cos(tt - i.x) + sin(tt + i.y), sin(tt - i.y) + cos(tt + i.x));
    c += 1.0 / length(vec2(p.x / (sin(i.x + tt) / 0.005), p.y / (cos(i.y + tt) / 0.005)));
  }
  c = 1.17 - pow(c / 5.0, 1.4);
  return min(pow(abs(c), 8.0), 1.0);
}

void main() {
  vec2 suv = vec2(gl_FragCoord.x, uRes.y - gl_FragCoord.y) / uRes; // y points down
  vec2 px = suv * uView;
  float aspect = uView.x / uView.y;
  float y = suv.y;
  float t = uTime;
  float shallow = 1.0 - uDepth;

  // surface normal from the ripple field; it refracts everything below
  float hL = texture(uHeight, suv - vec2(uTexel.x, 0.0)).r;
  float hR = texture(uHeight, suv + vec2(uTexel.x, 0.0)).r;
  float hU = texture(uHeight, suv - vec2(0.0, uTexel.y)).r;
  float hD = texture(uHeight, suv + vec2(0.0, uTexel.y)).r;
  vec2 n = vec2(hR - hL, hD - hU);
  vec2 refr = n * 0.02;

  // water body: lighter near the surface, sinking into the page background
  float g = smoothstep(-0.15, 1.05, y);
  vec3 tint = mix(vec3(0.07, 0.17, 0.32), vec3(0.043, 0.067, 0.125), g);
  float tintA = (1.0 - g) * mix(0.25, 0.7, shallow);

  // caustics, each channel sampled slightly apart for dispersion
  vec2 cuv = (px + vec2(0.0, uScroll * 0.12)) / 560.0 + refr * 3.0;
  float ct = t * 0.42 + 23.0;
  vec2 spread = vec2(0.004, 0.002) * (1.0 + uSwell * 4.0);
  vec3 caus = vec3(caustic(cuv - spread, ct), caustic(cuv, ct), caustic(cuv + spread, ct));
  float causMask = mix(0.5, 0.12, smoothstep(0.0, 1.0, y)) * mix(0.45, 1.0, shallow);
  vec3 light = caus * vec3(0.5, 0.72, 1.0) * causMask * 0.3;

  // god rays fanning from a light source above the screen
  vec2 src = vec2(aspect * (0.5 + sin(t * 0.06) * 0.22), -0.4);
  vec2 d = vec2(suv.x * aspect, y) - src + refr * 0.6;
  float ang = atan(d.x, d.y);
  float shafts = pow(noise1(ang * 21.0 + t * 0.16), 3.0) * 0.6
               + pow(noise1(ang * 47.0 - t * 0.25 + 10.0), 5.0) * 0.5
               + pow(noise1(ang * 9.0 + t * 0.07 + 4.0), 2.0) * 0.35;
  float rayFade = exp(-length(d) * 1.5) * (1.0 - smoothstep(0.0, 1.2, y));
  light += vec3(0.42, 0.62, 1.0) * shafts * rayFade * 0.4 * mix(0.25, 1.0, shallow);

  // glare of the surface seen from below
  light += vec3(0.6, 0.8, 1.0) * exp(-y * 11.0) * (0.45 + 0.55 * caus.g) * 0.2 * shallow;

  // ripples catch the light on one flank and shade the other
  light += vec3(0.7, 0.85, 1.0) * clamp((n.x * 0.6 - n.y * 0.8) * 2.2, -0.06, 0.5) * 0.28;

  // marine snow: three parallax layers of drifting specks
  for (int i = 0; i < 3; i++) {
    float fi = float(i);
    float cell = 74.0 - fi * 18.0;
    vec2 p = (px + vec2(sin(t * 0.09 + fi) * 24.0, -t * (5.0 + fi * 4.0) + uScroll * (0.04 + fi * 0.05)) + refr * 60.0) / cell;
    vec2 id = floor(p);
    float h = hash(id + fi * 17.0);
    if (h > 0.5) continue;
    vec2 pos = vec2(hash(id + 3.1), hash(id + 7.7)) * 0.7 + 0.15;
    pos += vec2(sin(t * 0.6 + h * 40.0), cos(t * 0.5 + h * 30.0)) * 0.08;
    float dist = length((fract(p) - pos) * cell);
    float size = 0.6 + fi * 0.45 + hash(id + 1.3) * 0.8;
    float twinkle = 0.55 + 0.45 * sin(t * (1.0 + h * 2.0) + h * 50.0);
    light += vec3(0.75, 0.85, 1.0) * (1.0 - smoothstep(0.0, size + 0.8, dist)) * twinkle * (0.1 + fi * 0.07);
  }

  // bubbles: a couple per column, rising with a wobble, popping at the surface
  float colW = 90.0;
  float cx = floor(px.x / colW);
  for (int k = 0; k < 2; k++) {
    float id = cx * 2.0 + float(k);
    if (hash(vec2(id, 4.2)) > 0.32) continue;
    float h2 = hash(vec2(id, 9.1));
    float h3 = hash(vec2(id, 2.6));
    float h4 = hash(vec2(id, 5.3));
    float r = 1.6 + h2 * 4.2;
    float speed = 28.0 + r * 14.0;
    float travel = uView.y + 120.0;
    float yb = uView.y + 60.0 - mod(t * speed + h3 * travel, travel);
    float xb = (cx + 0.5) * colW + (h4 - 0.5) * colW * 0.35 + sin(t * (1.4 + h2) + h3 * 20.0) * (3.0 + r);
    vec2 dd = px - vec2(xb, yb);
    dd.y *= 1.0 + 0.14 * sin(t * 7.0 + h3 * 30.0);
    float dist = length(dd);
    float ring = (1.0 - smoothstep(r - 0.2, r + 0.9, dist)) * smoothstep(r - 1.8, r - 0.3, dist);
    float glint = 1.0 - smoothstep(0.0, r * 0.38, length(dd + vec2(r * 0.38)));
    float body = (1.0 - smoothstep(r - 0.6, r + 0.4, dist)) * 0.07;
    float alive = smoothstep(0.0, 70.0, yb);
    light += vec3(0.78, 0.88, 1.0) * (ring * 0.42 + glint * 0.55 + body) * alive;
  }

  float vignette = 1.0 - 0.3 * pow(length(suv - 0.5) * 1.3, 2.0);
  vec3 col = max(tint * tintA + light * vignette, 0.0);
  col += (hash(gl_FragCoord.xy + fract(t * 7.0)) - 0.5) / 255.0; // dither away gradient banding
  outColor = vec4(col, tintA) * uFade;
}`;

  const mouse = { x: 0, y: 0, active: false, last: 0 };
  const scroll = { current: window.scrollY, swell: 0 };
  let program = null;
  let uniforms = {};
  let heightTex = null;
  let width = 0;
  let height = 0;
  let scale = 0.6; // drawing buffer resolution relative to CSS px; lowered if frames run slow
  let gw = 0;
  let gh = 0;
  let current = null; // ripple field, this step
  let previousField = null; // ripple field, last step
  let frame = 0;
  let clock = 0;
  let previous = 0;
  let accumulator = 0;
  let fadeIn = 0;
  let nextDrip = 0;
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
    ['uRes', 'uView', 'uTime', 'uScroll', 'uDepth', 'uSwell', 'uFade', 'uHeight', 'uTexel']
      .forEach(name => { uniforms[name] = gl.getUniformLocation(program, name); });

    heightTex = gl.createTexture();
    gl.activeTexture(gl.TEXTURE0);
    gl.bindTexture(gl.TEXTURE_2D, heightTex);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
    gl.uniform1i(uniforms.uHeight, 0);
  };

  const sizeBuffer = () => {
    canvas.width = Math.max(1, Math.round(width * scale));
    canvas.height = Math.max(1, Math.round(height * scale));
    gl.viewport(0, 0, canvas.width, canvas.height);
  };

  const resize = () => {
    width = window.innerWidth;
    height = window.innerHeight;
    scale = Math.min(window.devicePixelRatio || 1, 1.5) * (width < 700 ? 0.55 : 0.6);
    sizeBuffer();

    gw = Math.min(180, Math.ceil(width / 9));
    gh = Math.max(8, Math.round(gw * height / width));
    current = new Float32Array(gw * gh);
    previousField = new Float32Array(gw * gh);
    gl.bindTexture(gl.TEXTURE_2D, heightTex);
    gl.texImage2D(gl.TEXTURE_2D, 0, gl.R16F, gw, gh, 0, gl.RED, gl.FLOAT, current);
  };

  // drop a smooth bump into the ripple field at a viewport position
  const drop = (x, y, strength, radius) => {
    if (!current) return;
    const cx = (x / width) * gw;
    const cy = (y / height) * gh;
    const r = Math.max(1.2, (radius / width) * gw);
    const span = Math.ceil(r * 2);
    for (let j = Math.max(1, Math.floor(cy - span)); j < Math.min(gh - 1, cy + span); j += 1) {
      for (let i = Math.max(1, Math.floor(cx - span)); i < Math.min(gw - 1, cx + span); i += 1) {
        const d2 = ((i - cx) ** 2 + (j - cy) ** 2) / (r * r);
        if (d2 < 4) current[j * gw + i] += strength * Math.exp(-d2 * 1.5);
      }
    }
  };

  // one step of the discrete wave equation; each cell is pulled by its four neighbours
  const simulate = dt => {
    clock += dt;
    const before = scroll.current;
    scroll.current = damp(scroll.current, window.scrollY, 8, dt);
    const speed = Math.abs(scroll.current - before) / dt;
    scroll.swell = damp(scroll.swell, Math.min(speed / 2500, 0.6), 3, dt);

    for (let j = 1; j < gh - 1; j += 1) {
      for (let i = 1; i < gw - 1; i += 1) {
        const k = j * gw + i;
        previousField[k] = ((current[k - 1] + current[k + 1] + current[k - gw] + current[k + gw]) * 0.5 - previousField[k]) * DAMPING;
      }
    }
    [current, previousField] = [previousField, current];

    // light ambient drips keep the surface alive when nobody touches it
    if (clock > nextDrip) {
      nextDrip = clock + 0.6 + Math.random() * 1.4;
      drop(Math.random() * width, Math.random() * height, 0.35 + scroll.swell, 14);
    }
    fadeIn = Math.min(1, fadeIn + dt / 1.4);
  };

  const draw = () => {
    gl.bindTexture(gl.TEXTURE_2D, heightTex);
    gl.texSubImage2D(gl.TEXTURE_2D, 0, 0, 0, gw, gh, gl.RED, gl.FLOAT, current);
    const maxScroll = Math.max(1, document.documentElement.scrollHeight - height);
    gl.uniform2f(uniforms.uRes, canvas.width, canvas.height);
    gl.uniform2f(uniforms.uView, width, height);
    gl.uniform1f(uniforms.uTime, clock);
    gl.uniform1f(uniforms.uScroll, scroll.current);
    gl.uniform1f(uniforms.uDepth, Math.min(1, scroll.current / maxScroll));
    gl.uniform1f(uniforms.uSwell, scroll.swell);
    gl.uniform1f(uniforms.uFade, easeOutCubic(fadeIn));
    gl.uniform2f(uniforms.uTexel, 1 / gw, 1 / gh);
    gl.drawArrays(gl.TRIANGLES, 0, 3);
  };

  // drop the resolution step by step while frames keep running long
  const adapt = elapsed => {
    slowFrames = elapsed > 0.024 ? slowFrames + 1 : Math.max(0, slowFrames - 1);
    if (slowFrames > 45 && scale > 0.35) {
      slowFrames = 0;
      scale *= 0.8;
      sizeBuffer();
    }
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
    adapt(elapsed);
    frame = requestAnimationFrame(loop);
  };

  const start = () => {
    cancelAnimationFrame(frame);
    frame = 0;
    previous = 0;
    accumulator = 0;
    if (gl.isContextLost()) return;
    if (reducedMotion.matches) {
      fadeIn = 1;
      scroll.current = window.scrollY;
      if (!clock) clock = 12; // a still frame with the rays already spread out
      draw();
    } else if (!document.hidden) {
      frame = requestAnimationFrame(loop);
    }
  };

  window.addEventListener('pointermove', event => {
    if (event.pointerType !== 'mouse' || reducedMotion.matches) return;
    const now = performance.now();
    if (mouse.active && now - mouse.last < 100) {
      const dt = Math.max((now - mouse.last) / 1000, 0.008);
      const speed = Math.min(Math.hypot(event.clientX - mouse.x, event.clientY - mouse.y) / dt, 3000);
      if (speed > 40) drop(event.clientX, event.clientY, Math.min(speed / 900, 1.6), 16);
    }
    mouse.x = event.clientX;
    mouse.y = event.clientY;
    mouse.last = now;
    mouse.active = true;
  }, { passive: true });
  window.addEventListener('pointerdown', event => {
    if (reducedMotion.matches) return;
    drop(event.clientX, event.clientY, 2.4, 22);
  }, { passive: true });
  document.addEventListener('mouseout', event => { if (!event.relatedTarget) mouse.active = false; });
  window.addEventListener('resize', () => { resize(); if (reducedMotion.matches) draw(); });
  window.addEventListener('scroll', () => { if (reducedMotion.matches) { scroll.current = window.scrollY; draw(); } }, { passive: true });
  document.addEventListener('visibilitychange', start);
  reducedMotion.addEventListener('change', start);
  canvas.addEventListener('webglcontextlost', event => { event.preventDefault(); cancelAnimationFrame(frame); });
  canvas.addEventListener('webglcontextrestored', () => { setup(); resize(); start(); });

  try {
    setup();
  } catch (error) {
    console.warn('Water background disabled:', error);
    canvas.remove();
    return;
  }
  resize();
  start();
})();
