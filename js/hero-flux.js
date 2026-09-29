(function () {
  'use strict';

  var container = document.querySelector('.hero__visual');
  if (!container || typeof THREE === 'undefined') return;

  var renderer;
  try {
    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false });
  } catch (err) {
    return;
  }

  var isMobile = window.innerWidth < 768;
  var COUNT_SCALE = isMobile ? 0.45 : 1;

  var CONFIG = {
    r1: 0.46,
    r2: 0.34,
    rFinal: 0.76,
    sep0: 3.4,
    contact: 1.0,
    orbit0: 0.8,
    orbitTilt: 0.32,
    timeScale: 1.0,
    startTime: 12.5,

    inspiral: 14.0,
    plunge: 0.5,
    ringdown: 7.0,
    fadeIn: 1.5,
    fadeOut: 1.5,
    rdShape: 0.20,
    rdDecay: 0.8,
    rdFreq: 9.0,
    flash: 1.6,

    perDisk: Math.round(12000 * COUNT_SCALE),
    diskIn: 1.5,
    diskOut: 4.2,
    diskLife: 4.0,
    inflow: 0.55,
    spin: 1.6,
    thick: 0.12,
    turb: 0.20,
    turbFreq: 0.55,
    twist: 2.2,
    streamRatio: 0.18,
    diskSize: 6.9,
    diskOpacity: 0.28,

    gwSpeed: 1.5,
    gwAmp: 0.35,
    gwLift: 0.22,
    gwGlow: 0.9,
    gwDecay: 1.2,
    burst: 0.55,

    dust: Math.round(46000 * COUNT_SCALE),
    dustInner: 0.8,
    dustOuter: 9.5,
    dustBias: 1.5,
    dustSize: 3.75,
    dustOpacity: 0.14,
    dustDrift: 0.32,
    dustFreq: 0.20,

    voidK: 1.85,
    voidFK: 0.89,
    einsteinK: 1.16,
    ringGlow: 1.7,
    beam: 0.75,
    beamDir: new THREE.Vector3(1.0, 0.15, 0.35),

    mouseRadius: 0.9,
    mouseForce: 0.55,
    autoRotate: 0.045,
    seed: 5
  };

  var PRESET = {
    bg: 0x07000f,
    colorA: 0x6a00ff, colorB: 0xc9a6ff, merge: 0xffffff, shadow: 0x000000,
    diskOpacity: 0.28, dustOpacity: 0.15
  };

  var _s = CONFIG.seed >>> 0;
  var rand = function () {
    _s = (_s + 0x6D2B79F5) >>> 0;
    var t = _s;
    t = Math.imul(t ^ (t >>> 15), t | 1);
    t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };

  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
  renderer.setClearColor(PRESET.bg, 1);
  container.appendChild(renderer.domElement);

  var scene = new THREE.Scene();
  var camera = new THREE.PerspectiveCamera(36, 1, 0.1, 200);
  var CAM_BASE = new THREE.Vector3(0.8, 1.9, 11.0);

  var root = new THREE.Group();
  scene.add(root);

  var ORB_N = new THREE.Vector3(0, Math.cos(CONFIG.orbitTilt), Math.sin(CONFIG.orbitTilt));
  var ORB_E1 = new THREE.Vector3(1, 0, 0);
  var ORB_E2 = new THREE.Vector3().crossVectors(ORB_N, ORB_E1);

  var DISK_N = [
    ORB_N.clone().addScaledVector(ORB_E1, 0.35).addScaledVector(ORB_E2, 0.10).normalize(),
    ORB_N.clone().addScaledVector(ORB_E1, -0.22).addScaledVector(ORB_E2, 0.30).normalize()
  ];

  // 3D simplex noise (Ashima Arts / Stefan Gustavson, MIT)
  var NOISE_GLSL = `
vec3 mod289(vec3 x){return x-floor(x*(1.0/289.0))*289.0;}
vec4 mod289(vec4 x){return x-floor(x*(1.0/289.0))*289.0;}
vec4 permute(vec4 x){return mod289(((x*34.0)+1.0)*x);}
vec4 taylorInvSqrt(vec4 r){return 1.79284291400159-0.85373472095314*r;}
float snoise(vec3 v){
  const vec2 C=vec2(1.0/6.0,1.0/3.0); const vec4 D=vec4(0.0,0.5,1.0,2.0);
  vec3 i=floor(v+dot(v,C.yyy)); vec3 x0=v-i+dot(i,C.xxx);
  vec3 g=step(x0.yzx,x0.xyz); vec3 l=1.0-g; vec3 i1=min(g.xyz,l.zxy); vec3 i2=max(g.xyz,l.zxy);
  vec3 x1=x0-i1+C.xxx; vec3 x2=x0-i2+C.yyy; vec3 x3=x0-D.yyy;
  i=mod289(i);
  vec4 p=permute(permute(permute(i.z+vec4(0.0,i1.z,i2.z,1.0))+i.y+vec4(0.0,i1.y,i2.y,1.0))+i.x+vec4(0.0,i1.x,i2.x,1.0));
  float n_=0.142857142857; vec3 ns=n_*D.wyz-D.xzx;
  vec4 j=p-49.0*floor(p*ns.z*ns.z); vec4 x_=floor(j*ns.z); vec4 y_=floor(j-7.0*x_);
  vec4 x=x_*ns.x+ns.yyyy; vec4 y=y_*ns.x+ns.yyyy; vec4 h=1.0-abs(x)-abs(y);
  vec4 b0=vec4(x.xy,y.xy); vec4 b1=vec4(x.zw,y.zw);
  vec4 s0=floor(b0)*2.0+1.0; vec4 s1=floor(b1)*2.0+1.0; vec4 sh=-step(h,vec4(0.0));
  vec4 a0=b0.xzyw+s0.xzyw*sh.xxyy; vec4 a1=b1.xzyw+s1.xzyw*sh.zzww;
  vec3 p0=vec3(a0.xy,h.x); vec3 p1=vec3(a0.zw,h.y); vec3 p2=vec3(a1.xy,h.z); vec3 p3=vec3(a1.zw,h.w);
  vec4 norm=taylorInvSqrt(vec4(dot(p0,p0),dot(p1,p1),dot(p2,p2),dot(p3,p3)));
  p0*=norm.x; p1*=norm.y; p2*=norm.z; p3*=norm.w;
  vec4 m=max(0.6-vec4(dot(x0,x0),dot(x1,x1),dot(x2,x2),dot(x3,x3)),0.0); m=m*m;
  return 42.0*dot(m*m,vec4(dot(p0,x0),dot(p1,x1),dot(p2,x2),dot(p3,x3)));
}`;

  var COMMON = NOISE_GLSL + `
attribute float aRand;
uniform vec3  uHoleP[2];
uniform float uHoleR[2];
uniform float uVoidK, uVoidFK;
uniform float uEinK, uRingGlow, uBeam;
uniform vec3  uBeamDir;
uniform vec3  uRayO, uRayD;
uniform float uMouseR, uMouseF, uMouseOn;
uniform float uSize, uPixelRatio, uOpacity;
uniform vec3  uMerge;
varying vec3  vColor;
varying float vAlpha;

vec3 safeNorm(vec3 v){ return v / max(length(v), 1e-4); }

vec3 pushOut(vec3 p, vec3 anchor, float R, float F){
  vec3  d  = p - anchor;
  float dl = length(d);
  return p + (d / max(dl, 1e-4)) * F * (1.0 - smoothstep(0.0, R, dl));
}

vec3 openVoid(vec3 p){
  for (int k = 0; k < 2; k++) {
    float R = uHoleR[k];
    if (R > 1e-3) p = pushOut(p, uHoleP[k], uVoidK * R, uVoidFK * R);
  }
  return p;
}

vec3 mousePush(vec3 p){
  if (uMouseOn < 0.5) return p;
  float s = max(dot(p - uRayO, uRayD), 0.0);
  return pushOut(p, uRayO + uRayD * s, uMouseR, uMouseF);
}

// 중력렌즈 화면공간 근사: θ = ½(β + √(β² + 4θ_E²)), 렌즈 2개 이동량 합산, 전경 파티클 제외
void emit(vec3 p, vec3 baseColor, float alpha, float sizeVar){
  float merge = 0.0;
  for (int k = 0; k < 2; k++) {
    float R = uHoleR[k];
    if (R > 1e-3) merge = max(merge, 1.0 - smoothstep(0.16 * R, 2.2 * R, length(p - uHoleP[k])));
  }
  vec3 col = mix(baseColor, uMerge, merge * 0.8);

  vec4 mv   = modelViewMatrix * vec4(p, 1.0);
  vec4 clip = projectionMatrix * mv;

  float boost = 1.0;
  float stretch = 1.0;

  if (clip.w > 0.0) {
    float fy = projectionMatrix[1][1];
    float aspect = fy / projectionMatrix[0][0];
    vec2 ndc = clip.xy / clip.w;
    vec2 shift = vec2(0.0);

    for (int k = 0; k < 2; k++) {
      float R = uHoleR[k];
      if (R < 1e-3) continue;
      vec4 hMV   = modelViewMatrix * vec4(uHoleP[k], 1.0);
      vec4 hClip = projectionMatrix * hMV;
      if (hClip.w <= 0.0) continue;

      vec2 rel = (ndc - hClip.xy / hClip.w) * vec2(aspect, 1.0);
      float b = max(length(rel), 1e-5);
      float thE = uEinK * R * fy / max(abs(hMV.z), 0.001);
      float th  = 0.5 * (b + sqrt(b * b + 4.0 * thE * thE));

      float depthW = 1.13 * R;
      float behind = 1.0 - smoothstep(hMV.z - depthW, hMV.z + depthW, mv.z);
      float s = mix(1.0, th / b, behind);
      shift   += rel * (s - 1.0);
      stretch *= s;

      float g = (th - thE) / (thE * 0.45);
      float ring = behind * exp(-g * g);
      float side = dot(safeNorm(p - uHoleP[k]), uBeamDir);
      boost *= (1.0 + uRingGlow * ring) * (1.0 + uBeam * ring * side);
    }

    ndc += shift / vec2(aspect, 1.0);
    clip.xy = ndc * clip.w;
  }

  vColor = col;
  vAlpha = alpha * max(boost, 0.0);
  gl_PointSize = clamp(
    uSize * uPixelRatio * sizeVar * sqrt(stretch) * (10.0 / max(-mv.z, 0.1)),
    0.0, 39.0 * uPixelRatio
  );
  gl_Position = clip;
}
`;

  var FRAG = `
uniform float uMultiply;
varying vec3  vColor;
varying float vAlpha;
void main(){
  float d = length(gl_PointCoord - 0.5);
  float a = smoothstep(0.5, 0.04, d) * vAlpha;
  if (a < 0.003) discard;
  if (uMultiply > 0.5) {
    gl_FragColor = vec4(mix(vec3(1.0), vColor, clamp(a, 0.0, 1.0)), 1.0);
  } else {
    gl_FragColor = vec4(vColor, a);
  }
}`;

  var DISK_VERT = COMMON + `
attribute float aPhase;
attribute float aStream;
uniform float uTime, uLife, uInflow, uSpin, uThick, uTurb, uTurbFreq, uTwist, uFade;
uniform float uDiskIn, uDiskOut, uScale, uOtherR, uStreamMix;
uniform vec3  uCenter, uE1, uE2, uN, uOther, uOrbN;
uniform vec3  uColor, uColorOther;

void main(){
  float cyc = uTime / uLife + aPhase;
  float u   = fract(cyc);
  float gen = floor(cyc);

  float k  = uInflow;
  float r0 = mix(uDiskIn, uDiskOut, position.x);
  float rr = r0 * (1.0 - k * u);
  float th = position.y * 6.2831853 + gen * 2.3999632
           + uSpin * uLife * pow(r0, -1.5) * (2.0 / k) * (inversesqrt(1.0 - k * u) - 1.0);
  vec3 local = (uE1 * cos(th) + uE2 * sin(th)) * rr + uN * (position.z * uThick * rr);

  float tw = uTwist * log(rr);
  float lx = dot(local, uE1), ly = dot(local, uE2);
  vec3 q = vec3(lx * cos(tw) - ly * sin(tw), lx * sin(tw) + ly * cos(tw), dot(local, uN));
  q = q * uTurbFreq + vec3(0.0, 0.0, uTime * 0.07);
  vec3 n = vec3(snoise(q), snoise(q + 19.7), snoise(q - 11.3));
  vec3 disk = uCenter + (local + n * uTurb * rr) * uScale;

  vec3 toO  = uOther - uCenter;
  float dist = length(toO);
  vec3 dirO = toO / max(dist, 1e-4);
  vec3 lead = cross(uOrbN, dirO);
  vec3 P0 = uCenter + safeNorm(dirO - lead * 0.6) * uDiskOut * 0.75 * uScale;
  vec3 P2 = uOther  + safeNorm(-lead - dirO * 0.3) * uOtherR * 2.2;
  vec3 P1 = (uCenter + uOther) * 0.5 - lead * dist * 0.28;
  vec3 path = mix(mix(P0, P1, u), mix(P1, P2, u), u);
  float width = uScale * 0.6 * (0.25 + 0.9 * sin(3.14159 * u));
  vec3 stream = path
              + (lead * (position.y - 0.5) * 2.0 + uOrbN * position.z * 0.6) * width
              + n * uTurb * 2.5 * uScale;

  float sm = aStream * uStreamMix;
  vec3 p = mix(disk, stream, sm);
  vec3 col = mix(uColor, mix(uColor, uColorOther, smoothstep(0.3, 1.0, u)), sm);

  p = mousePush(openVoid(p));
  float fade = smoothstep(0.0, 0.12, u) * (1.0 - smoothstep(0.7, 1.0, u));
  emit(p, col, fade * uFade * uOpacity * (0.45 + aRand * 0.85), 0.55 + aRand * 0.85);
}`;

  var DUST_VERT = COMMON + `
uniform float uTime, uAmp, uFreq;
uniform vec3  uColorA, uColorB;
uniform vec3  uOrbE1, uOrbE2, uOrbN;
uniform float uT, uTm, uTc, uTp, uW0, uPhiM, uWm, uAc0;
uniform float uGwC, uGwAmp, uGwLift, uGwGlow, uGwDecay, uGwOn, uBurst;

float orbitPhase(float t){
  if (t < uTm) return uW0 * uTc * 1.6 * (1.0 - pow(1.0 - t / uTc, 0.625));
  return uPhiM + uWm * (t - uTm);
}
float strainAmp(float t){
  if (t < uTm) return uAc0 * pow(1.0 - t / uTc, -0.25);
  return exp(-max(t - uTm - uTp, 0.0) / uGwDecay);
}

vec3 gravWave(vec3 p, out float glow){
  float px = dot(p, uOrbE1), py = dot(p, uOrbE2);
  float r  = length(p);
  float tr = uT - r / uGwC;
  float h  = strainAmp(tr) * cos(2.0 * atan(py, px + 1e-5) - 2.0 * orbitPhase(tr));
  float env = smoothstep(0.8, 2.6, r) * 3.0 / (r + 2.0) * uGwOn;
  float x = tr - uTm - uTp;
  float pulse = exp(-x * x / 0.06) * env;
  vec3 radial = (px * uOrbE1 + py * uOrbE2) / max(length(vec2(px, py)), 1e-4);
  glow = max(1.0 + uGwGlow * h * env + 2.0 * pulse, 0.0);
  return p + radial * (h * env * uGwAmp + pulse * uBurst) + uOrbN * (h * env * uGwLift);
}

void main(){
  vec3 q = position * uFreq + vec3(0.0, uTime * 0.05, 0.0);
  vec3 d = vec3(snoise(q), snoise(q + 19.7), snoise(q - 11.3));
  float glow;
  vec3 p = gravWave(position + d * uAmp, glow);
  p = mousePush(openVoid(p));
  emit(p, mix(uColorA, uColorB, aRand), uOpacity * glow * (0.25 + aRand * 0.95), 0.5 + aRand * 0.7);
}`;

  var SHARED = {
    uHoleP: { value: [new THREE.Vector3(), new THREE.Vector3()] },
    uHoleR: { value: [0, 0] },
    uVoidK: { value: CONFIG.voidK },
    uVoidFK: { value: CONFIG.voidFK },
    uEinK: { value: CONFIG.einsteinK },
    uRingGlow: { value: CONFIG.ringGlow },
    uBeam: { value: CONFIG.beam },
    uBeamDir: { value: CONFIG.beamDir.clone().normalize() },
    uRayO: { value: new THREE.Vector3() },
    uRayD: { value: new THREE.Vector3(0, 0, -1) },
    uMouseR: { value: CONFIG.mouseRadius },
    uMouseF: { value: CONFIG.mouseForce },
    uMouseOn: { value: 0 },
    uPixelRatio: { value: renderer.getPixelRatio() },
    uMerge: { value: new THREE.Color(PRESET.merge) },
    uMultiply: { value: 0 },
    uTime: { value: 0 },
    uFade: { value: 1 },
    uOrbE1: { value: ORB_E1 },
    uOrbE2: { value: ORB_E2 },
    uOrbN: { value: ORB_N },
    uT: { value: 0 }, uTm: { value: 0 }, uTc: { value: 1 }, uTp: { value: 0 },
    uW0: { value: 0 }, uPhiM: { value: 0 }, uWm: { value: 0 }, uAc0: { value: 0 },
    uGwC: { value: CONFIG.gwSpeed },
    uGwAmp: { value: CONFIG.gwAmp },
    uGwLift: { value: CONFIG.gwLift },
    uGwGlow: { value: CONFIG.gwGlow },
    uGwDecay: { value: CONFIG.gwDecay },
    uGwOn: { value: 1 },
    uBurst: { value: CONFIG.burst }
  };

  function uniformsWith(own) {
    return Object.assign({}, SHARED, own);
  }

  function makePoints(geo, mat) {
    var pts = new THREE.Points(geo, mat);
    pts.frustumCulled = false;
    root.add(pts);
    return pts;
  }

  function shaderMat(uniforms, vert) {
    return new THREE.ShaderMaterial({
      uniforms: uniformsWith(uniforms),
      vertexShader: vert,
      fragmentShader: FRAG,
      transparent: true, depthWrite: false, depthTest: true,
      blending: THREE.AdditiveBlending
    });
  }

  var disks = [];
  function makeDisk(index) {
    var n = CONFIG.perDisk;
    var pos = new Float32Array(n * 3);
    var phase = new Float32Array(n);
    var rnd = new Float32Array(n);
    var stream = new Float32Array(n);

    for (var i = 0; i < n; i++) {
      pos[i * 3] = Math.pow(rand(), 0.7);
      pos[i * 3 + 1] = rand();
      pos[i * 3 + 2] = (rand() + rand() + rand() - 1.5) * 1.2;
      phase[i] = rand();
      rnd[i] = rand();
      stream[i] = rand() < CONFIG.streamRatio ? 1 : 0;
    }

    var geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
    geo.setAttribute('aPhase', new THREE.BufferAttribute(phase, 1));
    geo.setAttribute('aRand', new THREE.BufferAttribute(rnd, 1));
    geo.setAttribute('aStream', new THREE.BufferAttribute(stream, 1));

    var mat = shaderMat({
      uSize: { value: CONFIG.diskSize },
      uOpacity: { value: PRESET.diskOpacity },
      uLife: { value: CONFIG.diskLife },
      uInflow: { value: CONFIG.inflow },
      uSpin: { value: CONFIG.spin },
      uThick: { value: CONFIG.thick },
      uTurb: { value: CONFIG.turb },
      uTurbFreq: { value: CONFIG.turbFreq },
      uTwist: { value: CONFIG.twist },
      uDiskIn: { value: CONFIG.diskIn },
      uDiskOut: { value: CONFIG.diskOut },
      uScale: { value: 1 },
      uOtherR: { value: 1 },
      uStreamMix: { value: 1 },
      uCenter: { value: new THREE.Vector3() },
      uOther: { value: new THREE.Vector3() },
      uE1: { value: new THREE.Vector3() },
      uE2: { value: new THREE.Vector3() },
      uN: { value: new THREE.Vector3() },
      uColor: { value: new THREE.Color(index === 0 ? PRESET.colorA : PRESET.colorB) },
      uColorOther: { value: new THREE.Color(index === 0 ? PRESET.colorB : PRESET.colorA) }
    }, DISK_VERT);

    disks.push(makePoints(geo, mat));
  }

  function makeDust() {
    var n = CONFIG.dust;
    var pos = new Float32Array(n * 3);
    var rnd = new Float32Array(n);
    var span = CONFIG.dustOuter - CONFIG.dustInner;

    for (var i = 0; i < n; i++) {
      var u = rand() * 2 - 1;
      var th = rand() * Math.PI * 2;
      var sq = Math.sqrt(Math.max(1 - u * u, 0));
      var r = CONFIG.dustInner + Math.pow(rand(), CONFIG.dustBias) * span;
      pos[i * 3] = sq * Math.cos(th) * r;
      pos[i * 3 + 1] = u * r;
      pos[i * 3 + 2] = sq * Math.sin(th) * r;
      rnd[i] = rand();
    }

    var geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
    geo.setAttribute('aRand', new THREE.BufferAttribute(rnd, 1));

    var mat = shaderMat({
      uSize: { value: CONFIG.dustSize },
      uOpacity: { value: PRESET.dustOpacity },
      uAmp: { value: CONFIG.dustDrift },
      uFreq: { value: CONFIG.dustFreq },
      uColorA: { value: new THREE.Color(PRESET.colorA) },
      uColorB: { value: new THREE.Color(PRESET.colorB) }
    }, DUST_VERT);

    return makePoints(geo, mat);
  }

  makeDisk(0);
  makeDisk(1);
  makeDust();

  var holeMat = new THREE.MeshBasicMaterial({ color: PRESET.shadow });
  var unitSphere = new THREE.SphereGeometry(1, 48, 48);
  function makeHoleMesh() {
    var m = new THREE.Mesh(unitSphere, holeMat);
    m.renderOrder = -1;
    root.add(m);
    return m;
  }
  var hole1 = makeHoleMesh();
  var hole2 = makeHoleMesh();
  var remnant = makeHoleMesh();

  function timeline() {
    var R1 = CONFIG.r1, R2 = CONFIG.r2, Rf = CONFIG.rFinal;
    var M = R1 + R2;
    var a0 = CONFIG.sep0, ac = M * CONFIG.contact;
    var tm = CONFIG.inspiral, Tp = CONFIG.plunge;
    var Tc = tm / (1 - Math.pow(ac / a0, 4));
    var xm = 1 - tm / Tc;
    return {
      R1: R1, R2: R2, Rf: Rf, M: M, a0: a0, ac: ac, tm: tm, Tp: Tp, Tc: Tc,
      phiM: CONFIG.orbit0 * Tc * 1.6 * (1 - Math.pow(xm, 0.625)),
      wm: CONFIG.orbit0 * Math.pow(xm, -0.375),
      L: tm + Tp + CONFIG.ringdown
    };
  }

  var ease = function (x) { return x * x * (3 - 2 * x); };
  var clamp = THREE.MathUtils.clamp;
  var lerp = THREE.MathUtils.lerp;
  var H1 = new THREE.Vector3(), H2 = new THREE.Vector3();
  var _dir = new THREE.Vector3(), _y = new THREE.Vector3(), _mat = new THREE.Matrix4();
  var _x = new THREE.Vector3(1, 0, 0);

  function setBasis(n, e1, e2) {
    e1.copy(_x).addScaledVector(n, -n.dot(_x)).normalize();
    e2.crossVectors(n, e1);
  }

  function updateBinary(t, T) {
    var a, phi, sp;
    if (t < T.tm) {
      var x = 1 - t / T.Tc;
      a = T.a0 * Math.pow(x, 0.25);
      phi = CONFIG.orbit0 * T.Tc * 1.6 * (1 - Math.pow(x, 0.625));
      sp = 0;
    } else {
      sp = Math.min((t - T.tm) / T.Tp, 1);
      a = T.ac * Math.pow(1 - sp, 1.5);
      phi = T.phiM + T.wm * Math.min(t - T.tm, T.Tp);
    }
    var es = ease(sp);

    var fade = ease(clamp(t / CONFIG.fadeIn, 0, 1)) * ease(clamp((T.L - t) / CONFIG.fadeOut, 0, 1));

    _dir.copy(ORB_E1).multiplyScalar(Math.cos(phi)).addScaledVector(ORB_E2, Math.sin(phi));
    H1.copy(_dir).multiplyScalar(-a * T.R2 / T.M);
    H2.copy(_dir).multiplyScalar(a * T.R1 / T.M);

    SHARED.uHoleP.value[0].copy(H1);
    SHARED.uHoleP.value[1].copy(H2);
    SHARED.uHoleR.value[0] = lerp(T.R1, T.Rf, es) * fade;
    SHARED.uHoleR.value[1] = lerp(T.R2, 0, es) * fade;

    hole1.visible = hole2.visible = sp < 1;
    hole1.position.copy(H1); hole1.scale.setScalar(T.R1 * fade);
    hole2.position.copy(H2); hole2.scale.setScalar(T.R2 * fade);

    remnant.visible = sp > 0;
    if (sp > 0) {
      var major, minor, ang;
      if (sp < 1) {
        var env = Math.max(a * T.R2 / T.M + T.R1, a * T.R1 / T.M + T.R2);
        major = lerp(env * 0.95, T.Rf * (1 + CONFIG.rdShape), es);
        minor = lerp(Math.min(T.R1, T.R2) * 0.6, T.Rf * (1 - CONFIG.rdShape * 0.5), es);
        ang = phi;
      } else {
        var tau = t - T.tm - T.Tp;
        var dk = Math.exp(-tau / CONFIG.rdDecay);
        var e = CONFIG.rdShape * dk * Math.cos(CONFIG.rdFreq * tau);
        major = T.Rf * (1 + e);
        minor = T.Rf * (1 - e * 0.5);
        ang = T.phiM + T.wm * T.Tp + T.wm * CONFIG.rdDecay * (1 - dk);
      }
      _dir.copy(ORB_E1).multiplyScalar(Math.cos(ang)).addScaledVector(ORB_E2, Math.sin(ang));
      _y.crossVectors(ORB_N, _dir);
      _mat.makeBasis(_dir, _y, ORB_N);
      remnant.quaternion.setFromRotationMatrix(_mat);
      remnant.scale.set(major * fade, minor * fade, minor * fade);
    }

    disks.forEach(function (d, i) {
      var u = d.material.uniforms;
      u.uCenter.value.copy(i === 0 ? H1 : H2);
      u.uOther.value.copy(i === 0 ? H2 : H1);
      u.uScale.value = lerp(i === 0 ? T.R1 : T.R2, T.Rf, es);
      u.uOtherR.value = i === 0 ? T.R2 : T.R1;
      u.uStreamMix.value = 1 - es;
      u.uN.value.copy(DISK_N[i]).lerp(ORB_N, es).normalize();
      setBasis(u.uN.value, u.uE1.value, u.uE2.value);
    });
    SHARED.uFade.value = fade;

    var fl = (t - T.tm - T.Tp) / 0.4;
    SHARED.uRingGlow.value = CONFIG.ringGlow * (1 + CONFIG.flash * Math.exp(-fl * fl));

    SHARED.uT.value = t;
    SHARED.uTm.value = T.tm;
    SHARED.uTc.value = T.Tc;
    SHARED.uTp.value = T.Tp;
    SHARED.uW0.value = CONFIG.orbit0;
    SHARED.uPhiM.value = T.phiM;
    SHARED.uWm.value = T.wm;
    SHARED.uAc0.value = T.ac / T.a0;
    SHARED.uGwOn.value = fade;
  }

  var raycaster = new THREE.Raycaster();
  var ndc = new THREE.Vector2();
  var mouseOn = false;

  window.addEventListener('pointermove', function (e) {
    var rect = renderer.domElement.getBoundingClientRect();
    if (!rect.width || !rect.height) return;
    var x = (e.clientX - rect.left) / rect.width;
    var y = (e.clientY - rect.top) / rect.height;
    mouseOn = x >= 0 && x <= 1 && y >= 0 && y <= 1;
    ndc.set(x * 2 - 1, -y * 2 + 1);
  }, { passive: true });
  document.addEventListener('pointerleave', function () { mouseOn = false; });

  function resize() {
    var w = container.clientWidth, h = container.clientHeight;
    if (!w || !h) return;
    renderer.setSize(w, h, false);
    var aspect = w / h;
    camera.aspect = aspect;
    camera.position.copy(CAM_BASE).multiplyScalar(clamp(1.1 / aspect, 1, 2.2));
    camera.lookAt(0, 0, 0);
    camera.updateProjectionMatrix();
    SHARED.uPixelRatio.value = renderer.getPixelRatio();
  }

  var clock = new THREE.Clock();
  var _inv = new THREE.Matrix4();
  var simTime = CONFIG.startTime;
  var targetRY = 0;
  var inView = true;
  var rafId = 0;

  function frame() {
    rafId = 0;
    if (!inView || document.hidden) return;

    var dt = Math.min(clock.getDelta(), 0.05);
    simTime += dt * CONFIG.timeScale;
    targetRY += CONFIG.autoRotate * dt;
    root.rotation.y += (targetRY - root.rotation.y) * 0.08;
    root.updateMatrixWorld();

    var T = timeline();
    var t = ((simTime % T.L) + T.L) % T.L;
    updateBinary(t, T);
    SHARED.uTime.value = simTime;

    SHARED.uMouseOn.value = mouseOn ? 1 : 0;
    if (mouseOn) {
      raycaster.setFromCamera(ndc, camera);
      _inv.copy(root.matrixWorld).invert();
      SHARED.uRayO.value.copy(raycaster.ray.origin).applyMatrix4(_inv);
      SHARED.uRayD.value.copy(raycaster.ray.direction).transformDirection(_inv).normalize();
    }

    renderer.render(scene, camera);
    rafId = requestAnimationFrame(frame);
  }

  function start() {
    if (rafId || !inView || document.hidden) return;
    clock.getDelta();
    rafId = requestAnimationFrame(frame);
  }

  resize();
  if ('ResizeObserver' in window) new ResizeObserver(resize).observe(container);
  else window.addEventListener('resize', resize);

  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (entries) {
      inView = entries[entries.length - 1].isIntersecting;
      if (inView) start();
    }).observe(container);
  }
  document.addEventListener('visibilitychange', start);

  start();
})();
