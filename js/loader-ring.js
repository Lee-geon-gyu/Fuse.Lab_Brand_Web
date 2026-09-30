(() => {
  const loader = document.getElementById('loader');
  if (!loader || !window.THREE) return;
  if (/[?&]noloader(&|=|$)/.test(location.search)) return;

  const RINGS = 130;
  const SEGS = 220;
  const AXIS0 = 2.79;
  const TRAIL = 12;

  const GLSL_POS = `
    uniform float uTime;
    uniform float uAxis;
    uniform float uOpen;
    vec3 ringPt(float th, float k, float rnd) {
      float alpha = uOpen * (0.7 * pow(k, 1.9) * (1.0 + 0.14 * sin(uTime * (0.8 + rnd) + rnd * 37.0)) + 0.04 * sin(uTime * (1.1 + rnd) + rnd * 11.0));
      float rad = 1.3 * (1.0 + 0.03 * (rnd - 0.5) + 0.012 * sin(uTime * 1.4 + rnd * 30.0));
      vec3 ax = vec3(cos(uAxis), 0.0, sin(uAxis));
      vec3 pp = vec3(-sin(uAxis), 0.0, cos(uAxis));
      vec3 b = cos(alpha) * pp + sin(alpha) * vec3(0.0, 1.0, 0.0);
      return rad * (cos(th) * ax + sin(th) * b);
    }
  `;

  const VERT_RING = `
    attribute vec3 aP;
    uniform float uGain;
    varying vec3 vCol;
    ${GLSL_POS}
    void main() {
      float th = aP.x;
      float k = aP.y;
      float rnd = aP.z;
      vec3 p = ringPt(th, k, rnd);
      vec3 c = mix(vec3(0.32, 0.04, 1.0), vec3(0.44, 0.26, 1.0), rnd * 0.8 + k * 0.2);
      float ends = pow(abs(cos(th)), 24.0);
      float a = (0.5 + 0.5 * rnd) * (1.0 + 2.2 * exp(-k * 22.0)) * (1.0 + 0.8 * ends) * uGain;
      c = mix(c, vec3(0.6, 0.66, 1.0), ends * 0.5);
      vCol = c * a;
      gl_Position = projectionMatrix * modelViewMatrix * vec4(p, 1.0);
    }
  `;
  const FRAG_RING = `
    precision mediump float;
    varying vec3 vCol;
    void main() { gl_FragColor = vec4(vCol, max(vCol.r, max(vCol.g, vCol.b))); }
  `;

  const VERT_SPARK = `
    attribute float aTh;
    attribute float aK;
    attribute float aFade;
    attribute float aMove;
    uniform float uScale;
    uniform float uSize;
    varying float vFlick;
    ${GLSL_POS}
    void main() {
      float th = aTh + aMove * uTime * 0.9;
      vec3 p = ringPt(th, aK, 0.5);
      vFlick = (0.75 + 0.25 * sin(uTime * 5.0 + aTh * 5.0)) * aFade * smoothstep(0.2, 1.0, uOpen);
      gl_PointSize = uScale * uSize * (0.35 + 0.65 * aFade) * (1.0 + 0.3 * sin(uTime * 3.0 + aTh));
      gl_Position = projectionMatrix * modelViewMatrix * vec4(p, 1.0);
    }
  `;
  const FRAG_SPARK = `
    precision mediump float;
    uniform sampler2D uMap;
    uniform float uAlpha;
    varying float vFlick;
    void main() { vec4 c = texture2D(uMap, gl_PointCoord); gl_FragColor = vec4(c.rgb * c.a * vFlick * uAlpha, c.a * vFlick * uAlpha); }
  `;

  const makeSparkTexture = () => {
    const c = document.createElement('canvas');
    c.width = c.height = 128;
    const g = c.getContext('2d');
    const grad = g.createRadialGradient(64, 64, 0, 64, 64, 64);
    grad.addColorStop(0, 'rgba(255,255,255,1)');
    grad.addColorStop(0.08, 'rgba(255,255,255,1)');
    grad.addColorStop(0.22, 'rgba(200,160,255,0.55)');
    grad.addColorStop(0.55, 'rgba(120,40,255,0.14)');
    grad.addColorStop(1, 'rgba(90,0,255,0)');
    g.fillStyle = grad;
    g.fillRect(0, 0, 128, 128);
    return new THREE.CanvasTexture(c);
  };

  let renderer;
  const canvas = document.createElement('canvas');
  canvas.className = 'loader__ring loader__ring--gl';
  try {
    renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true, powerPreference: 'low-power' });
  } catch (e) { return; }
  renderer.setClearColor(0x000000, 0);

  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(35.37, 869 / 837, 0.1, 50); // 캔버스 1.5배 여유에 맞춘 fov (링 픽셀 크기 동일)
  camera.position.set(0, 0, 6.7);

  const group = new THREE.Group();
  group.rotation.set(-1.45, 0.15, 0.25);
  scene.add(group);

  const cols = SEGS + 1;
  const aP = new Float32Array(RINGS * cols * 3);
  const lineIdx = [];
  for (let i = 0; i < RINGS; i++) {
    const k = i / (RINGS - 1);
    const rnd = Math.random();
    for (let j = 0; j < cols; j++) {
      const o = (i * cols + j) * 3;
      aP[o] = (j / SEGS) * Math.PI * 2;
      aP[o + 1] = k;
      aP[o + 2] = rnd;
      if (j < SEGS) lineIdx.push(i * cols + j, i * cols + j + 1);
    }
  }

  const uniforms = { uTime: { value: 0 }, uAxis: { value: AXIS0 }, uOpen: { value: 0 } };
  const additive = { transparent: true, blending: THREE.CustomBlending, blendEquation: THREE.AddEquation, blendSrc: THREE.OneFactor, blendDst: THREE.OneFactor, blendSrcAlpha: THREE.OneFactor, blendDstAlpha: THREE.OneFactor, depthWrite: false, depthTest: false };

  const lineGeo = new THREE.BufferGeometry();
  lineGeo.setAttribute('position', new THREE.BufferAttribute(new Float32Array(RINGS * cols * 3), 3));
  lineGeo.setAttribute('aP', new THREE.BufferAttribute(aP, 3));
  lineGeo.setIndex(lineIdx);
  const lineMat = new THREE.ShaderMaterial({
    ...additive, uniforms: { ...uniforms, uGain: { value: 0.42 } }, vertexShader: VERT_RING, fragmentShader: FRAG_RING,
  });
  const lines = new THREE.LineSegments(lineGeo, lineMat);
  lines.frustumCulled = false;

  const sparkTex = makeSparkTexture();
  const makePoints = (th, k, fade, move, size, alpha) => {
    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.BufferAttribute(new Float32Array(th.length * 3), 3));
    geo.setAttribute('aTh', new THREE.BufferAttribute(new Float32Array(th), 1));
    geo.setAttribute('aK', new THREE.BufferAttribute(new Float32Array(k), 1));
    geo.setAttribute('aFade', new THREE.BufferAttribute(new Float32Array(fade), 1));
    geo.setAttribute('aMove', new THREE.BufferAttribute(new Float32Array(move), 1));
    const mat = new THREE.ShaderMaterial({
      ...additive, uniforms: { ...uniforms, uScale: { value: 100 }, uSize: { value: size }, uAlpha: { value: alpha }, uMap: { value: sparkTex } },
      vertexShader: VERT_SPARK, fragmentShader: FRAG_SPARK,
    });
    const pts = new THREE.Points(geo, mat);
    pts.frustumCulled = false;
    return { geo, mat, pts };
  };

  const sparks = makePoints([0, Math.PI, 1.6], [0.5, 0.5, 0.85], [1, 1, 1], [0, 0, 1], 0.14, 1);
  const trailTh = [];
  const trailFade = [];
  for (let n = 1; n <= TRAIL; n++) {
    trailTh.push(1.6 - n * 0.06);
    trailFade.push(Math.pow(1 - n / (TRAIL + 1), 2) * 0.55);
  }
  const trail = makePoints(trailTh, trailTh.map(() => 0.85), trailFade, trailTh.map(() => 1), 0.07, 0.8);
  const GLOW_N = 160;
  const glowTh = Array.from({ length: GLOW_N }, (_, i) => (i / GLOW_N) * Math.PI * 2);
  const glow = makePoints(glowTh, glowTh.map(() => 0.02), glowTh.map(() => 0.6), glowTh.map(() => 0), 0.11, 0.02);

  const spin = new THREE.Group();
  spin.add(glow.pts, lines, sparks.pts, trail.pts);
  group.add(spin);

  const pointSets = [sparks, trail, glow];
  let renderNow = null;
  const resize = () => {
    const w = canvas.offsetWidth || 869;
    const h = canvas.offsetHeight || 837;
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    renderer.setPixelRatio(dpr);
    renderer.setSize(w, h, false);
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    pointSets.forEach((p) => { p.mat.uniforms.uScale.value = h * dpr; });
    if (renderNow) renderNow();
  };

  loader.insertBefore(canvas, loader.firstChild);
  loader.classList.add('loader--gl');
  resize();
  window.addEventListener('resize', resize);

  const reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const easeOut = (x) => 1 - Math.pow(1 - Math.min(Math.max(x, 0), 1), 3);
  const easeOutBack = (x) => { const k = Math.min(Math.max(x, 0), 1) - 1; return 1 + 2.2 * k * k * k + 1.2 * k * k; };
  const baseGain = { line: lineMat.uniforms.uGain.value, sets: pointSets.map((p) => p.mat.uniforms.uAlpha.value) };

  let raf = 0;
  let alive = true;
  let exitAt = 0;
  const t0 = performance.now();
  const exitWatch = new MutationObserver(() => {
    if (!exitAt && loader.classList.contains('is-done')) exitAt = performance.now();
  });
  exitWatch.observe(loader, { attributes: true, attributeFilter: ['class'] });

  const frame = () => {
    if (!alive) return;
    const now = performance.now();
    const el = reduced ? 3 : (now - t0) / 1000;
    const draw = reduced ? 1 : easeOut(el / 0.6);
    const grow = reduced ? 1 : easeOutBack(el / 1.4);
    const open = reduced ? 1 : easeOut((el - 0.1) / 1.3);
    const ex = exitAt ? Math.min((now - exitAt) / 800, 1) : 0;
    const exIn = ex * ex;
    uniforms.uTime.value = el + 1.5;
    uniforms.uAxis.value = AXIS0 + (reduced ? 0 : el * 0.22);
    uniforms.uOpen.value = open;
    const fade = (1 - exIn) * draw;
    lineMat.uniforms.uGain.value = baseGain.line * fade;
    pointSets.forEach((p, i) => { p.mat.uniforms.uAlpha.value = baseGain.sets[i] * fade; });
    const wob = reduced ? 0 : 1;
    group.rotation.x = -1.45 + 0.35 * (1 - open) + wob * 0.06 * Math.sin(el * 1.1);
    group.rotation.y = 0.15 + wob * 0.12 * Math.sin(el * 0.8);
    group.rotation.z = 0.25 + wob * 0.05 * Math.sin(el * 0.6 + 1);
    spin.rotation.y = reduced ? 0 : -exIn * 3.2;
    group.scale.setScalar(0.35 + 0.65 * grow + exIn * 0.45);
    renderer.render(scene, camera);
    if (!reduced) raf = requestAnimationFrame(frame);
  };
  renderNow = reduced ? frame : null;
  frame();

  const dispose = () => {
    if (!alive) return;
    alive = false;
    cancelAnimationFrame(raf);
    exitWatch.disconnect();
    window.removeEventListener('resize', resize);
    lineGeo.dispose();
    lineMat.dispose();
    pointSets.forEach((p) => { p.geo.dispose(); p.mat.dispose(); });
    sparkTex.dispose();
    renderer.dispose();
    renderer.forceContextLoss();
  };
  window.addEventListener('loader:done', dispose, { once: true });
})();
