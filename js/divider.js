(() => {
  'use strict';

  const section = document.getElementById('divider');
  if (!section) return;
  const track = section.querySelector('.divider__track');
  const host = section.querySelector('.divider__sphere');
  const nextSection = section.nextElementSibling;
  const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
  let sculpture = null;
  let unavailable = false;

  // 기존 이미지는 WebGL 미지원·초기화 실패 시의 대체 이미지로 유지한다.
  function createSculpture(onComplete) {
    if (!host || !window.THREE) return null;
    let renderer;
    try {
      renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true, powerPreference: 'low-power' });
    } catch (_) {
      return null;
    }
    renderer.setClearColor(0x000000, 0);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.75));
    renderer.domElement.className = 'divider__canvas';
    renderer.domElement.setAttribute('aria-hidden', 'true');
    host.appendChild(renderer.domElement);

    const scene = new THREE.Scene();
    const camera = new THREE.OrthographicCamera(-1.55, 1.55, 1.42, -1.42, 0.1, 20);
    camera.position.z = 6;
    const vertexShader = `
      uniform float uRipple;
      uniform float uTime;
      varying vec3 vPosition;
      varying vec3 vNormal;
      varying vec3 vWorld;
      void main() {
        vec3 p = position;
        float wave = sin(p.x * 13.0 + uTime * 5.0) * sin(p.y * 11.0 - uTime * 4.0)
          + 0.45 * sin(p.z * 18.0 + p.x * 9.0 + uTime * 6.0);
        p += normal * wave * uRipple;
        vec4 world = vec4(p, 1.0);
        vec3 n = normal;
        #ifdef USE_INSTANCING
          world = instanceMatrix * world;
          n = mat3(instanceMatrix) * n;
        #endif
        world = modelMatrix * world;
        vWorld = world.xyz;
        vNormal = normalize(mat3(modelMatrix) * n);
        vPosition = p;
        gl_Position = projectionMatrix * viewMatrix * world;
      }
    `;

    // 실제 구체의 굴절 광선을 따라 내부 곡면을 교차하고 스튜디오 조명을 반사한다.
    // 완성 단계에는 기존 조형물의 디테일을 구면 재질에 합성해 원본의 인상을 유지한다.
    const fragmentShader = `
      precision highp float;
      uniform float uLayers;
      uniform float uOpacity;
      uniform sampler2D uReference;
      uniform float uReferenceReady;
      varying vec3 vPosition;
      varying vec3 vNormal;
      varying vec3 vWorld;
      mat2 rotate(float a) { return mat2(cos(a), -sin(a), sin(a), cos(a)); }
      vec3 studio(vec3 d) {
        vec3 c = mix(vec3(0.46, 0.39, 0.64), vec3(0.96, 0.96, 0.99), smoothstep(-0.5, 0.65, d.y));
        c = mix(c, vec3(0.31, 0.22, 0.52), exp(-pow((d.y + 0.34) / 0.16, 2.0)) * 0.58);
        float left = exp(-pow((d.x + 0.52) / 0.15, 2.0) - pow((d.y - 0.32) / 0.8, 2.0));
        float right = exp(-pow((d.x - 0.56) / 0.23, 2.0) - pow((d.y - 0.3) / 0.72, 2.0));
        float top = exp(-pow((d.x + 0.12) / 0.65, 2.0) - pow((d.y - 0.8) / 0.1, 2.0));
        c += vec3(0.95, 0.94, 1.0) * (left * 0.85 + right * 0.6 + top * 0.7);
        c += vec3(0.32, 0.13, 0.13) * exp(-pow((d.x + 0.7) / 0.18, 2.0) - pow((d.y - 0.15) / 0.32, 2.0));
        return c;
      }
      vec3 innerLayers(vec3 origin, vec3 direction, float distanceInside) {
        vec3 c = mix(vec3(0.80, 0.75, 0.94), vec3(0.98, 0.98, 1.0), smoothstep(-0.65, 0.8, origin.x));
        // 구체 안에서 비스듬히 겹치는 타원 유리막의 앞·뒤 곡면을 굴절해 본다.
        origin.xy = rotate(0.58) * origin.xy;
        direction.xy = rotate(0.58) * direction.xy;
        for (int i = 0; i < 3; i++) {
          float layer = float(i);
          vec3 center = vec3(-0.10 + 0.15 * layer, 0.72 - 0.69 * layer, -0.22 - 0.15 * layer);
          vec3 radii = vec3(1.34, 0.36 + 0.15 * layer, 0.86);
          vec3 o = (origin - center) / radii;
          vec3 d = direction / radii;
          float a = dot(d, d);
          float b = dot(o, d);
          float cc = dot(o, o) - 1.0;
          float discriminant = b * b - a * cc;
          if (discriminant > 0.0) {
            for (int side = 0; side < 2; side++) {
              float sign = side == 0 ? 1.0 : -1.0;
              float t = (-b + sign * sqrt(discriminant)) / a;
              vec3 p = origin + direction * t;
              if (t > 0.01 && t < distanceInside) {
                float mask = 1.0 - smoothstep(0.89, 0.99, dot(p, p));
                vec3 normal = normalize((p - center) / (radii * radii));
                float facing = abs(dot(normal, direction));
                float edge = pow(1.0 - facing, 2.2);
                vec3 reflected = reflect(direction, normal);
                reflected.xy = rotate(-0.58) * reflected.xy;
                vec3 tint = i == 0 ? vec3(0.50, 0.37, 0.78) : vec3(0.71, 0.68, 0.85);
                vec3 lavender = mix(tint, vec3(0.90, 0.89, 0.97), facing * 0.75);
                lavender = mix(lavender, min(studio(reflected), vec3(1.15)), 0.20 + 0.28 * edge);
                float opacity = (0.30 + 0.58 * edge) * mask * uLayers;
                c = mix(c, lavender, opacity);
              }
            }
          }
        }
        return c;
      }
      void main() {
        vec3 n = normalize(vNormal);
        vec3 view = vec3(0.0, 0.0, 1.0);
        float facing = max(dot(n, view), 0.0);
        float fresnel = 0.045 + 0.955 * pow(1.0 - facing, 4.2);
        vec3 ray = refract(-view, n, 1.0 / 1.46);
        vec3 origin = normalize(vPosition);
        float inside = max(0.0, -2.0 * dot(origin, ray));
        vec3 exitPoint = origin + ray * inside;
        vec3 exitRay = refract(ray, -normalize(exitPoint), 1.46);
        vec3 reflection = studio(reflect(-view, n));
        vec3 refraction = mix(vec3(0.95, 0.95, 0.96), studio(exitRay), 0.28);
        vec3 layers = uLayers > 0.0 ? innerLayers(origin, ray, inside) : refraction;
        vec3 c = mix(refraction, layers, uLayers * 0.86);
        c *= 0.90 + 0.10 * facing;
        float pool = exp(-dot(origin.xy - vec2(-0.22, 0.35), origin.xy - vec2(-0.22, 0.35)) * 2.8);
        c = mix(c, vec3(0.73, 0.66, 0.94), pool * 0.27 * uLayers);
        c = mix(c, reflection, 0.04 + fresnel * 0.86);
        float broad = pow(max(0.0, dot(n, normalize(vec3(0.58, 0.38, 0.9)))), 19.0);
        float strip = pow(max(0.0, dot(n, normalize(vec3(-0.62, 0.55, 0.43)))), 65.0);
        c += vec3(0.14, 0.15, 0.17) * broad + vec3(0.44, 0.43, 0.46) * strip;
        float rim = exp(-pow((facing - 0.09) / 0.035, 2.0));
        c = mix(c, vec3(0.47, 0.36, 0.73), rim * 0.36);
        float rimLight = 0.03 + 0.1 * smoothstep(-0.4, 0.7, n.x + n.y);
        c += rimLight * exp(-pow((facing - 0.19) / 0.035, 2.0));
        vec2 referenceUV = vec2(0.4961, 0.5005) + origin.xy * vec2(0.2487, 0.3652);
        vec4 reference = texture2D(uReference, referenceUV);
        float detail = uLayers * uReferenceReady;
        c = mix(c, reference.rgb, detail * 0.92);
        gl_FragColor = vec4(c, uOpacity * mix(1.0, reference.a, detail));
      }
    `;
    function glassMaterial(layers) {
      return new THREE.ShaderMaterial({
        uniforms: {
          uTime: { value: 0 }, uRipple: { value: 0 },
          uLayers: { value: layers }, uOpacity: { value: 1 },
          uReference: { value: null }, uReferenceReady: { value: 0 },
        },
        vertexShader, fragmentShader, transparent: true,
      });
    }
    const shellMaterial = glassMaterial(0);
    const referenceImage = host.querySelector('img');
    const referenceTexture = new THREE.Texture(referenceImage);
    referenceTexture.minFilter = THREE.LinearFilter;
    referenceTexture.generateMipmaps = false;
    shellMaterial.uniforms.uReference.value = referenceTexture;
    function onReferenceLoad() {
      if (disposed || !referenceImage.naturalWidth) return;
      referenceTexture.needsUpdate = true;
      shellMaterial.uniforms.uReferenceReady.value = 1;
      if (active && !contextLost) render();
    }
    const shellGeometry = new THREE.SphereGeometry(1, 96, 64);
    const shell = new THREE.Mesh(shellGeometry, shellMaterial);
    scene.add(shell);
    const dropCount = window.innerWidth <= 768 ? 28 : 40;
    const dropGeometry = new THREE.SphereGeometry(1, 24, 16);
    const dropMaterial = glassMaterial(0);
    const drops = new THREE.InstancedMesh(dropGeometry, dropMaterial, dropCount);
    drops.instanceMatrix.setUsage(THREE.DynamicDrawUsage);
    drops.frustumCulled = false;
    scene.add(drops);
    const dummy = new THREE.Object3D();
    const seeds = Array.from({ length: dropCount }, (_, i) => {
      const angle = i * 2.3999632297;
      const radius = 0.28 + 1.05 * Math.sqrt((i + 1) / dropCount);
      return {
        x: Math.cos(angle) * radius, y: Math.sin(angle) * radius * 0.88,
        z: Math.sin(i * 4.71) * 0.42,
        size: 0.018 + 0.072 * Math.pow(0.5 + 0.5 * Math.sin(i * 7.13), 2),
        appear: i < 7 ? i * 0.055 : 0.42 + (i - 7) / dropCount * 0.72,
        absorb: 1.6 + (i / dropCount) * 1.65,
      };
    });
    const ease = (a, b, t) => {
      const x = Math.max(0, Math.min(1, (t - a) / (b - a)));
      return x * x * (3 - 2 * x);
    };
    function pose(t) {
      // 물방울 → 군집 → 결합 → 흡수 → 내부 레이어 → 정착. 완성 후 RAF 종료.
      const grow = ease(1.3, 3.65, t);
      const radius = 0.04 + 1.295 * grow;
      shell.visible = t > 1.28;
      shell.scale.set(radius * (1 + 0.03 * grow), radius, radius);
      shell.position.y = 0.05 * grow;
      shellMaterial.uniforms.uTime.value = t;
      shellMaterial.uniforms.uRipple.value = 0.038 * ease(1.3, 1.8, t) * (1 - ease(2.25, 3.65, t));
      shellMaterial.uniforms.uLayers.value = ease(2.5, 4.2, t);
      shellMaterial.uniforms.uOpacity.value = ease(1.28, 1.55, t);
      drops.visible = t < 4.2;
      seeds.forEach((seed, i) => {
        const appear = ease(seed.appear, seed.appear + 0.28, t);
        const absorb = ease(seed.absorb, seed.absorb + 0.58, t);
        const cluster = 1 - 0.27 * ease(0.5, 1.3, t);
        const remaining = 1 - absorb;
        const drift = 0.055 * Math.sin(t * 2.2 + i) * remaining;
        // 중심으로 향하는 궤도를 휘어 접촉한 입자부터 표면으로 흡수한다.
        const angle = absorb * 0.45;
        const x = seed.x * Math.cos(angle) - seed.y * Math.sin(angle);
        const y = seed.x * Math.sin(angle) + seed.y * Math.cos(angle);
        dummy.position.set(x * cluster * remaining + drift, y * cluster * remaining, seed.z * remaining);
        dummy.scale.setScalar(Math.max(0.00001, seed.size * appear * (1 - ease(0.45, 1, absorb))));
        dummy.updateMatrix();
        drops.setMatrixAt(i, dummy.matrix);
      });
      drops.instanceMatrix.needsUpdate = true;
    }
    let elapsed = motion.matches ? 5 : 0;
    let previous = 0;
    let frame = 0;
    let active = false;
    let contextLost = false;
    let disposed = false;
    let completed = false;
    const canvas = renderer.domElement;
    function render() {
      pose(elapsed);
      renderer.render(scene, camera);
    }
    function stop() {
      cancelAnimationFrame(frame);
      frame = 0;
      previous = 0;
    }
    function complete() {
      if (completed) return;
      completed = true;
      onComplete();
    }
    function tick() {
      frame = 0;
      if (!active || document.hidden || contextLost || disposed) return;
      const now = performance.now();
      if (previous) elapsed = Math.min(5, elapsed + (now - previous) / 1000);
      previous = now;
      render();
      if (elapsed < 5) frame = requestAnimationFrame(tick);
      else {
        previous = 0;
        complete();
      }
    }
    function setActive(value) {
      active = value;
      stop();
      if (!active || document.hidden || contextLost || disposed) return;
      if (elapsed < 5) frame = requestAnimationFrame(tick);
      else {
        render();
        complete();
      }
    }
    function reset() {
      setActive(false);
      elapsed = motion.matches ? 5 : 0;
      completed = false;
      if (!contextLost && !disposed) render();
    }
    function restart() {
      elapsed = motion.matches || contextLost ? 5 : 0;
      completed = false;
      setActive(true);
      if (contextLost) complete();
    }
    function showFinal() {
      setActive(false);
      elapsed = 5;
      completed = true;
      if (!contextLost && !disposed) render();
    }
    function resize() {
      if (disposed || contextLost) return;
      const width = host.clientWidth;
      const height = host.clientHeight;
      if (!width || !height) return;
      const halfHeight = 1.42;
      const halfWidth = halfHeight * width / height;
      camera.left = -halfWidth;
      camera.right = halfWidth;
      camera.top = halfHeight;
      camera.bottom = -halfHeight;
      camera.updateProjectionMatrix();
      renderer.setSize(width, height, false);
      render();
    }
    function onVisibility() { setActive(active); }
    function onMotion() {
      if (motion.matches) elapsed = 5;
      setActive(active);
    }
    function onContextLost(event) {
      event.preventDefault();
      contextLost = true;
      stop();
      host.classList.remove('is-webgl');
      elapsed = 5;
      complete();
    }
    function onContextRestored() {
      contextLost = false;
      resize();
      host.classList.add('is-webgl');
      setActive(active);
    }
    let sizeObserver;
    function dispose() {
      if (disposed) return;
      stop();
      disposed = true;
      sizeObserver?.disconnect();
      document.removeEventListener('visibilitychange', onVisibility);
      motion.removeEventListener('change', onMotion);
      window.removeEventListener('pagehide', onPageHide);
      canvas.removeEventListener('webglcontextlost', onContextLost);
      canvas.removeEventListener('webglcontextrestored', onContextRestored);
      shellGeometry.dispose();
      shellMaterial.dispose();
      referenceTexture.dispose();
      referenceImage.removeEventListener('load', onReferenceLoad);
      dropGeometry.dispose();
      dropMaterial.dispose();
      renderer.dispose();
      canvas.remove();
      host.classList.remove('is-webgl');
    }
    function onPageHide(event) {
      if (!event.persisted) dispose();
    }
    try {
      if (referenceImage.complete && referenceImage.naturalWidth) onReferenceLoad();
      else referenceImage.addEventListener('load', onReferenceLoad, { once: true });
      resize();
      if (renderer.info.programs.some(program => program.diagnostics && !program.diagnostics.runnable)) {
        dispose();
        return null;
      }
      sizeObserver = new ResizeObserver(resize);
      sizeObserver.observe(host);
      document.addEventListener('visibilitychange', onVisibility);
      motion.addEventListener('change', onMotion);
      window.addEventListener('pagehide', onPageHide);
      canvas.addEventListener('webglcontextlost', onContextLost);
      canvas.addEventListener('webglcontextrestored', onContextRestored);
      host.classList.add('is-webgl');
    } catch (_) {
      dispose();
      return null;
    }
    return { setActive, reset, restart, showFinal };
  }

  let pinTrigger = null;
  let playbackTrigger = null;
  let playing = false;
  let pausedLenis = null;
  let touchY = 0;
  let reflowing = false;
  let resizeFrame = 0;
  let textReveal = null;
  let textFrame = 0;
  let revealStarted = 0;
  let waitingForScroll = false;
  let lastDownInput = 0;
  section.dataset.dividerPlayback = 'idle';
  section.classList.add('is-sequenced');

  function prepareText() {
    const title = section.querySelector('.divider__title');
    const description = section.querySelector('.divider__desc');
    function split(element) {
      if (!element) return [];
      const fragment = document.createDocumentFragment();
      element.textContent.trim().split(/(\s+)/).forEach(part => {
        if (/^\s+$/.test(part)) { fragment.append(' '); return; }
        const word = document.createElement('span');
        word.className = 'divider__word';
        Array.from(part).forEach(character => {
          const letter = document.createElement('span');
          letter.className = 'divider__char';
          letter.textContent = character;
          word.append(letter);
        });
        fragment.append(word);
      });
      element.replaceChildren(fragment);
      return [...element.querySelectorAll('.divider__char')];
    }
    const titleChars = split(title);
    const descChars = split(description);
    // 원래 그라디언트의 위치별 색을 글자에 나눠 칠해 빠른 등장 중에도 재페인트를 줄인다.
    if (title) {
      const gradient = getComputedStyle(title).backgroundImage;
      const stops = (gradient.match(/rgba?\([^)]+\)/g) || []).map(color => color.match(/[\d.]+/g).slice(0, 3).map(Number));
      const first = stops[0] || [22, 28, 34];
      const last = stops[stops.length - 1] || [91, 24, 241];
      title.style.backgroundImage = 'none';
      const paint = () => {
        const colors = titleChars.map(letter => {
          const position = Math.max(0, Math.min(1, (letter.offsetLeft - title.offsetLeft + letter.offsetWidth / 2) / title.offsetWidth));
          return `rgb(${first.map((value, index) => Math.round(value + (last[index] - value) * position)).join(',')})`;
        });
        titleChars.forEach((letter, index) => {
          letter.style.color = colors[index];
          letter.style.webkitTextFillColor = 'currentColor';
        });
      };
      paint();
      window.addEventListener('resize', paint);
      document.fonts?.ready.then(paint);
    }
    textReveal = gsap.timeline({ paused: true });
    const from = { opacity: 0, yPercent: 45 };
    textReveal.fromTo(titleChars, from, {
      opacity: 1, yPercent: 0, duration: 0.26,
      stagger: { amount: 0.22 }, ease: 'power3.out',
    }, 0);
    textReveal.fromTo(descChars, from, {
      opacity: 1, yPercent: 0, duration: 0.22,
      stagger: { amount: 0.18 }, ease: 'power3.out',
    }, 0.08);
    // 숨겨진 초기 상태에서 글자별 transform을 미리 계산해 첫 등장 프레임이 지연되지 않게 한다.
    textReveal.progress(1, true).pause(0, true);
    return { title, titleChars };
  }

  function createExitTransition({ title, titleChars }) {
    const pinLength = 1.2;
    const revealAt = 0.8;
    const middle = (titleChars.length - 1) / 2;
    gsap.set(host, { opacity: 1 });
    document.documentElement.classList.add('fx-divider-pin');
    const placeNextSection = () => {
      if (nextSection) nextSection.style.marginTop = `${-(section.offsetHeight + window.innerHeight * pinLength * (1 - revealAt))}px`;
    };
    placeNextSection();
    ScrollTrigger.addEventListener('refreshInit', placeNextSection);
    // 원래의 제목 확대·글자 확산·블러 전환은 자동 재생하지 않고 다음 스크롤에 연결한다.
    const exit = gsap.timeline({
      defaults: { ease: 'none' },
      scrollTrigger: {
        id: 'divider-sculpture-pin', trigger: section, start: 'top top',
        end: () => `+=${window.innerHeight * pinLength}`,
        pin: true, scrub: true, refreshPriority: 1, invalidateOnRefresh: true,
        onUpdate: ({ progress }) => {
          section.dataset.headerTheme = progress > 0.5 ? 'light' : 'dark';
          if (nextSection) nextSection.dataset.fxCovered = String(progress < revealAt);
        },
      },
    });
    exit.to(title, { scale: 3.2, ease: 'power1.in', duration: 0.75 }, 0)
      .to(titleChars, {
        x: index => (index - middle) * title.offsetHeight * 0.18,
        ease: 'power1.in', duration: 0.75,
      }, 0)
      .to(title, { filter: 'blur(16px)', ease: 'power2.in', duration: 0.45 }, 0.15)
      .to(title, { opacity: 0, ease: 'power1.in', duration: 0.3 }, 0.45)
      .to([section.querySelector('.divider__desc'), section.querySelector('.divider__marquee')], { opacity: 0, duration: 0.25 }, 0)
      .fromTo(host, { opacity: 1 }, { opacity: 0, duration: 0.4, immediateRender: false }, 0.15)
      .to(section, { backgroundColor: getComputedStyle(nextSection || section).backgroundColor, duration: 0.45 }, 0.3)
      .to(section, { opacity: 0, duration: 1 - revealAt }, revealAt);
    return exit.scrollTrigger;
  }

  function ensureSculpture() {
    if (!sculpture && !unavailable) {
      sculpture = createSculpture(finishPlayback);
      unavailable = !sculpture;
    }
    return sculpture;
  }

  // 재생 중 아래 방향 입력은 기다리게 하고, 위 방향 입력은 즉시 통과시킨다.
  function onWheel(event) {
    if (event.ctrlKey || Math.abs(event.deltaX) > Math.abs(event.deltaY)) return;
    if (event.deltaY < 0) cancelPlayback();
    else if (event.deltaY > 0) {
      const now = performance.now();
      const freshGesture = now - lastDownInput > 180;
      lastDownInput = now;
      if (waitingForScroll && freshGesture) {
        waitingForScroll = false;
        releaseScroll();
      } else if (event.cancelable) event.preventDefault();
    }
  }
  function onTouchStart(event) {
    touchY = event.touches[0]?.clientY ?? 0;
    if (waitingForScroll) {
      waitingForScroll = false;
      releaseScroll();
    }
  }
  function onTouchMove(event) {
    const nextY = event.touches[0]?.clientY ?? touchY;
    const delta = touchY - nextY;
    touchY = nextY;
    if (delta < -2) cancelPlayback();
    else if (delta > 0 && event.cancelable) event.preventDefault();
  }
  function onKeyDown(event) {
    if (event.target.closest?.('input, textarea, select, button, a, [contenteditable="true"]')) return;
    if (['ArrowUp', 'PageUp', 'Home', 'Escape'].includes(event.key) || (event.key === ' ' && event.shiftKey)) {
      cancelPlayback();
    } else if (['ArrowDown', 'PageDown', 'End', ' '].includes(event.key)) {
      if (waitingForScroll) {
        waitingForScroll = false;
        releaseScroll();
      } else event.preventDefault();
    }
  }
  function releaseScroll() {
    window.removeEventListener('wheel', onWheel, true);
    window.removeEventListener('touchstart', onTouchStart, true);
    window.removeEventListener('touchmove', onTouchMove, true);
    window.removeEventListener('keydown', onKeyDown, true);
    if (pausedLenis) {
      pausedLenis.start();
      pausedLenis = null;
    }
  }
  function cancelPlayback(reset = false) {
    playing = false;
    waitingForScroll = false;
    cancelAnimationFrame(resizeFrame);
    cancelAnimationFrame(textFrame);
    reflowing = false;
    releaseScroll();
    sculpture?.setActive(false);
    textReveal?.pause();
    if (reset) textReveal?.pause(0, true);
    if (reset) sculpture?.reset();
    section.dataset.dividerPlayback = 'idle';
  }
  function alignPin() {
    const top = playbackTrigger.start + 1;
    if (window.lenis) window.lenis.scrollTo(top, { immediate: true, force: true });
    else window.scrollTo({ top, behavior: 'instant' });
  }
  function beginPlayback(trigger) {
    if (playing || reflowing || window.ScrollTrigger?.isRefreshing || document.body.classList.contains('is-loading')) return;
    playbackTrigger = trigger;
    section.classList.remove('is-pre');
    ensureSculpture();
    playing = true;
    waitingForScroll = false;
    textReveal?.pause(0, true);
    section.dataset.dividerPlayback = 'playing';
    if (!motion.matches) {
      window.addEventListener('wheel', onWheel, { capture: true, passive: false });
      window.addEventListener('touchstart', onTouchStart, { capture: true, passive: true });
      window.addEventListener('touchmove', onTouchMove, { capture: true, passive: false });
      window.addEventListener('keydown', onKeyDown, true);
      alignPin();
      if (window.lenis && !window.lenis.isStopped) {
        pausedLenis = window.lenis;
        pausedLenis.stop();
      }
    }
    if (sculpture) sculpture.restart();
    else finishPlayback();
  }
  function finishPlayback() {
    if (!playing) return;
    if (textReveal && !motion.matches) {
      section.dataset.dividerPlayback = 'revealing';
      textReveal.pause(0, true);
      revealStarted = performance.now();
      textFrame = requestAnimationFrame(revealText);
    } else {
      textReveal?.progress(1, true).pause();
      readyForScroll();
    }
  }
  function revealText() {
    if (!playing || section.dataset.dividerPlayback !== 'revealing') return;
    const progress = Math.min(1, (performance.now() - revealStarted) / (textReveal.duration() * 1000));
    textReveal.progress(progress, true);
    if (progress < 1) textFrame = requestAnimationFrame(revealText);
    else readyForScroll();
  }
  function readyForScroll() {
    if (!playing) return;
    playing = false;
    section.dataset.dividerPlayback = 'complete';
    if (pinTrigger && !motion.matches && playbackTrigger.isActive) waitingForScroll = true;
    else releaseScroll();
  }

  function setupPin() {
    if (!window.gsap || !window.ScrollTrigger || motion.matches) {
      section.classList.remove('is-sequenced');
      return;
    }
    gsap.registerPlugin(ScrollTrigger);
    pinTrigger = createExitTransition(prepareText());
    playbackTrigger = ScrollTrigger.create({
      id: 'divider-sculpture-playback', trigger: section,
      start: () => pinTrigger.start, end: () => pinTrigger.end,
      onEnter: beginPlayback,
      onEnterBack: () => {
        if (reflowing || ScrollTrigger.isRefreshing) return;
        cancelPlayback();
        ensureSculpture()?.showFinal();
        textReveal?.progress(1, true).pause();
        section.dataset.dividerPlayback = 'complete';
      },
      onLeave: () => {
        if (reflowing || ScrollTrigger.isRefreshing) return;
        if (playing || waitingForScroll) cancelPlayback();
        else sculpture?.setActive(false);
      },
      onLeaveBack: () => { if (!reflowing && !ScrollTrigger.isRefreshing) cancelPlayback(true); },
      onRefresh: () => { if (playing || waitingForScroll) alignPin(); },
    });
    ScrollTrigger.sort();
    ScrollTrigger.refresh();
    const enterCurrent = () => {
      const atStart = Math.abs(window.scrollY - playbackTrigger.start) < 1;
      if ((playbackTrigger.isActive || atStart) && section.dataset.dividerPlayback === 'idle') beginPlayback(playbackTrigger);
    };
    window.addEventListener('loader:done', enterCurrent);
    window.addEventListener('load', enterCurrent, { once: true });
    // 뷰포트 변경에 따른 scroll anchoring을 이탈로 처리하지 않고 새 핀 위치에 맞춘다.
    window.addEventListener('resize', () => {
      if (!playing && !waitingForScroll) return;
      reflowing = true;
      cancelAnimationFrame(resizeFrame);
      resizeFrame = requestAnimationFrame(() => {
        ScrollTrigger.refresh();
        alignPin();
        reflowing = false;
      });
    });
    window.addEventListener('pagehide', () => cancelPlayback());
    motion.addEventListener('change', () => {
      if (motion.matches) {
        textReveal?.progress(1, true).pause();
        waitingForScroll = false;
        if (playing) readyForScroll();
        else releaseScroll();
      }
    });
    requestAnimationFrame(enterCurrent);
  }

  section.classList.add('is-pre');
  const io = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      const inView = entry.isIntersecting && entry.intersectionRatio >= 0.25;
      if (track) track.classList.toggle('is-paused', !inView);
      if (inView) {
        section.classList.remove('is-pre');
        ensureSculpture();
      }
      // 핀이 준비되면 재생 시작은 top top 진입 콜백만 담당한다.
      if (!pinTrigger) {
        if (inView && sculpture) {
          playing = true;
          section.dataset.dividerPlayback = 'playing';
          sculpture.restart();
        } else cancelPlayback(true);
      }
    });
  }, { threshold: 0.25 });
  const init = () => { setupPin(); io.observe(section); };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
  else init();
})();
