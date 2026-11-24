import * as THREE from 'three';

const DEFAULT_PHRASES = [
    'BODEGA LOS OLIVOS · DESPENSA PERUANA · COSECHA DEL DÍA',
    'PEDIDOS DIRECTOS POR WHATSAPP · ATENCIÓN TODOS LOS DÍAS',
    'GRANOS ANDINOS · FRUTAS FRESCAS · PAN ARTESANAL',
    'YAPE, PLIN Y TARJETAS SIN RECARGO · TU BODEGA DE BARRIO',
];

/**
 * Draw crisp serif/sans typography onto a satin-weave offscreen canvas.
 */
function paintSatinCanvas(canvas, { text, baseColor, midColor, edgeColor, textColor, stripeColor }) {
    const { width, height } = canvas;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    ctx.clearRect(0, 0, width, height);

    const bg = ctx.createLinearGradient(0, 0, 0, height);
    bg.addColorStop(0.0, edgeColor);
    bg.addColorStop(0.18, baseColor);
    bg.addColorStop(0.5, midColor);
    bg.addColorStop(0.82, baseColor);
    bg.addColorStop(1.0, edgeColor);
    ctx.fillStyle = bg;
    ctx.fillRect(0, 0, width, height);

    // Subtle satin weave threads
    ctx.fillStyle = 'rgba(255, 255, 255, 0.04)';
    for (let x = 0; x < width; x += 6) {
        ctx.fillRect(x, 0, 1, height);
    }

    // Editorial border pinstripes
    ctx.fillStyle = stripeColor;
    ctx.fillRect(0, 15, width, 3);
    ctx.fillRect(0, height - 18, width, 3);

    ctx.font = '700 84px "Plus Jakarta Sans", "Inter", system-ui, -apple-system, sans-serif';
    ctx.textBaseline = 'middle';
    ctx.textAlign = 'left';

    const unit = `${text}   •   `;
    const measuredWidth = ctx.measureText(unit).width || 900;
    const repeats = Math.max(1, Math.round(width / measuredWidth));
    const slotWidth = width / repeats;
    const scaleX = Math.min(1.08, (slotWidth * 0.94) / measuredWidth);

    ctx.fillStyle = textColor;
    ctx.shadowColor = 'rgba(0, 0, 0, 0.2)';
    ctx.shadowBlur = 4;
    ctx.shadowOffsetY = 2;

    for (let i = 0; i < repeats; i++) {
        ctx.save();
        ctx.translate(i * slotWidth + slotWidth * 0.03, height * 0.51);
        ctx.scale(scaleX, 1);
        ctx.fillText(unit, 0, 0);
        ctx.restore();
    }
}

function createSatinTexture(options, anisotropy) {
    const canvas = document.createElement('canvas');
    canvas.width = 2048;
    canvas.height = 256;

    paintSatinCanvas(canvas, options);

    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;
    texture.wrapS = THREE.RepeatWrapping;
    texture.wrapT = THREE.ClampToEdgeWrapping;
    texture.minFilter = THREE.LinearMipmapLinearFilter;
    texture.magFilter = THREE.LinearFilter;
    texture.anisotropy = anisotropy;
    texture.needsUpdate = true;

    return { canvas, texture };
}

function buildRibbonGeometry(segmentsU, segmentsV) {
    const vertCount = (segmentsU + 1) * (segmentsV + 1);
    const positions = new Float32Array(vertCount * 3);
    const uvs = new Float32Array(vertCount * 2);
    const indices = [];

    for (let i = 0; i <= segmentsU; i++) {
        const u = i / segmentsU;
        for (let j = 0; j <= segmentsV; j++) {
            const v = j / segmentsV;
            const idx = i * (segmentsV + 1) + j;
            uvs[idx * 2] = u;
            uvs[idx * 2 + 1] = 1 - v;
        }
    }

    for (let i = 0; i < segmentsU; i++) {
        for (let j = 0; j < segmentsV; j++) {
            const a = i * (segmentsV + 1) + j;
            const b = (i + 1) * (segmentsV + 1) + j;
            const c = a + 1;
            const d = b + 1;
            indices.push(a, b, c, c, b, d);
        }
    }

    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    geometry.setAttribute('uv', new THREE.BufferAttribute(uvs, 2));
    geometry.setIndex(indices);

    return geometry;
}

const _pPrev = new THREE.Vector3();
const _pCurr = new THREE.Vector3();
const _pNext = new THREE.Vector3();
const _tangent = new THREE.Vector3();
const _worldUp = new THREE.Vector3(0, 1, 0);
const _sideA = new THREE.Vector3();
const _sideB = new THREE.Vector3();
const _offsetDir = new THREE.Vector3();
const _normalDir = new THREE.Vector3();

function sampleCenter(u, time, config, ripple, target) {
    const x = (u - 0.5) * config.length;
    const waveA = Math.sin(u * Math.PI * config.waveFreqY + time * config.waveSpeed);
    const waveB = Math.cos(u * Math.PI * 1.35 - time * config.waveSpeed * 0.65);

    let y = waveA * config.ampY + waveB * (config.ampY * 0.25);
    let z = Math.cos(u * Math.PI * config.waveFreqZ + time * config.waveSpeed * 0.8) * config.ampZ;

    // Interactive tactile click ripple spreading outward from clicked point along ribbon
    if (ripple.amp > 0.001) {
        const dist = Math.abs(u - ripple.originU);
        const waveFront = ripple.age * 1.45;
        const envelope = Math.exp(-Math.pow((dist - waveFront * 0.45) * 7.5, 2)) * ripple.amp;
        y += Math.sin(dist * 24 - ripple.age * 11) * envelope * 0.42;
        z += Math.cos(dist * 18 - ripple.age * 9) * envelope * 0.48;
    }

    target.set(x, y, z);
    return target;
}

function updateRibbonVertices(ribbon, time, state) {
    const { geometry, segmentsU, segmentsV, config } = ribbon;
    const posAttr = geometry.getAttribute('position');
    const halfWidth = config.width * 0.5;
    const eps = 1 / segmentsU;

    for (let i = 0; i <= segmentsU; i++) {
        const u = i / segmentsU;
        sampleCenter(u, time, config, state.ripple, _pCurr);
        sampleCenter(Math.max(0, u - eps), time, config, state.ripple, _pPrev);
        sampleCenter(Math.min(1, u + eps), time, config, state.ripple, _pNext);

        _tangent.subVectors(_pNext, _pPrev).normalize();

        _sideA.crossVectors(_tangent, _worldUp);
        if (_sideA.lengthSq() < 1e-5) {
            _sideA.set(0, 0, 1);
        } else {
            _sideA.normalize();
        }
        _sideB.crossVectors(_sideA, _tangent).normalize();

        const twistAngle =
            config.baseTilt +
            state.twistOffset +
            u * Math.PI * 2 * config.twists +
            Math.sin(time * 0.7 + u * Math.PI * 2) * config.twistWobble +
            state.ptrY * 0.3 +
            state.ptrX * (u - 0.5) * 0.35;

        const cosA = Math.cos(twistAngle);
        const sinA = Math.sin(twistAngle);

        _offsetDir
            .set(0, 0, 0)
            .addScaledVector(_sideB, cosA)
            .addScaledVector(_sideA, sinA);

        _normalDir
            .set(0, 0, 0)
            .addScaledVector(_sideB, -sinA)
            .addScaledVector(_sideA, cosA);

        for (let j = 0; j <= segmentsV; j++) {
            const vCentered = j / segmentsV - 0.5;
            const span = vCentered * config.width;
            const camber = (0.25 - vCentered * vCentered) * halfWidth * 0.14;
            const idx = i * (segmentsV + 1) + j;

            posAttr.setXYZ(
                idx,
                _pCurr.x + _offsetDir.x * span + _normalDir.x * camber,
                _pCurr.y + _offsetDir.y * span + _normalDir.y * camber,
                _pCurr.z + _offsetDir.z * span + _normalDir.z * camber
            );
        }
    }

    posAttr.needsUpdate = true;
    geometry.computeVertexNormals();
}

export function initRibbonType(stageEl, options = {}) {
    if (!stageEl || stageEl.dataset.ribbonMounted === 'true') return;
    stageEl.dataset.ribbonMounted = 'true';

    const canvasHost = stageEl.querySelector('[data-ribbon-canvas]');
    const skeletonEl = stageEl.querySelector('[data-ribbon-skeleton]');
    const fallbackEl = stageEl.querySelector('[data-ribbon-fallback]');
    const statusEl = stageEl.querySelector('[data-ribbon-status]');
    const hintEl = stageEl.querySelector('[data-ribbon-hint]');
    if (!canvasHost) return;

    const prefersReducedMotion = Boolean(options.prefersReducedMotion);

    let phrases = DEFAULT_PHRASES;
    try {
        const parsed = JSON.parse(stageEl.dataset.ribbonPhrases || 'null');
        if (Array.isArray(parsed) && parsed.length) {
            phrases = parsed;
        }
    } catch {
        phrases = DEFAULT_PHRASES;
    }

    let renderer;
    try {
        renderer = new THREE.WebGLRenderer({
            antialias: true,
            alpha: true,
            powerPreference: 'high-performance',
        });
    } catch {
        skeletonEl?.classList.add('hidden');
        fallbackEl?.classList.remove('hidden');
        return;
    }

    const dpr = Math.min(window.devicePixelRatio || 1, 1.75);
    renderer.setPixelRatio(dpr);
    renderer.setClearColor(0x000000, 0);
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.1;
    renderer.domElement.className = 'block h-full w-full pointer-events-none select-none';
    skeletonEl?.classList.add('hidden');
    canvasHost.appendChild(renderer.domElement);

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(34, 4, 0.1, 60);
    camera.position.set(0, 0, 7.2);

    // Warm studio satin lighting (works cleanly over cream top & dark bottom seam)
    const ambient = new THREE.AmbientLight('#fff6ee', 1.05);
    scene.add(ambient);

    const keyLight = new THREE.DirectionalLight('#fffbf5', 2.45);
    keyLight.position.set(2.4, 5.2, 6.2);
    scene.add(keyLight);

    const rimWarm = new THREE.DirectionalLight('#ffc7a2', 1.45);
    rimWarm.position.set(-5.0, -2.5, 4.0);
    scene.add(rimWarm);

    const backFill = new THREE.DirectionalLight('#f4e2cf', 0.9);
    backFill.position.set(4.5, -3.8, -3.0);
    scene.add(backFill);

    const maxAnisotropy = Math.min(8, renderer.capabilities.getMaxAnisotropy() || 4);

    const palette = {
        baseColor: '#a64420',
        midColor: '#d45e32',
        edgeColor: '#6d280f',
        textColor: '#faf7ef',
        stripeColor: 'rgba(250, 247, 239, 0.44)',
    };

    let phraseIndex = 0;
    const { canvas: texCanvas, texture } = createSatinTexture(
        { text: phrases[phraseIndex], ...palette },
        maxAnisotropy
    );

    const material = new THREE.MeshPhysicalMaterial({
        map: texture,
        side: THREE.DoubleSide,
        roughness: 0.24,
        metalness: 0.08,
        sheen: 1.0,
        sheenRoughness: 0.26,
        sheenColor: new THREE.Color('#ffd6be'),
        clearcoat: 0.48,
        clearcoatRoughness: 0.22,
    });

    const segmentsU = 190;
    const segmentsV = 6;
    const geometry = buildRibbonGeometry(segmentsU, segmentsV);
    const mesh = new THREE.Mesh(geometry, material);
    scene.add(mesh);

    const ribbon = {
        mesh,
        geometry,
        material,
        texture,
        segmentsU,
        segmentsV,
        config: {
            length: 18,
            width: 1.05,
            ampY: 0.6,
            ampZ: 0.72,
            waveFreqY: 2.2,
            waveFreqZ: 1.85,
            waveSpeed: 0.56,
            twists: 1.75,
            baseTilt: 0.28,
            twistWobble: 0.16,
        },
    };

    const state = {
        ptrX: 0,
        ptrY: 0,
        targetPtrX: 0,
        targetPtrY: 0,
        twistOffset: 0,
        twistVelocity: 0,
        flowVelocity: 0.052,
        ripple: {
            originU: 0.5,
            amp: 0,
            age: 0,
        },
    };

    const resize = () => {
        const rect = canvasHost.getBoundingClientRect();
        const w = Math.max(1, Math.round(rect.width));
        const h = Math.max(1, Math.round(rect.height));

        camera.aspect = w / h;
        camera.position.z = w < 640 ? 6.2 : (w < 1024 ? 6.8 : 7.3);
        camera.updateProjectionMatrix();
        renderer.setSize(w, h, false);

        const vFovRad = (camera.fov * Math.PI) / 180;
        const visibleHeight = 2 * Math.tan(vFovRad / 2) * camera.position.z;
        const visibleWidth = visibleHeight * camera.aspect;

        // Guarantee full edge-to-edge coverage beyond left & right viewport bounds on mobile & desktop
        ribbon.config.length = Math.max(15, visibleWidth * 1.48);
        ribbon.config.width = visibleHeight * (w < 640 ? 0.34 : 0.37);
        ribbon.config.ampY = visibleHeight * 0.2;
        ribbon.config.ampZ = w < 640 ? 0.56 : 0.72;
        ribbon.config.waveFreqY = w < 640 ? 1.85 : 2.3;
        ribbon.config.twists = w < 640 ? 1.05 : 1.35;
        ribbon.config.baseTilt = -ribbon.config.twists * Math.PI + 0.14;

        const stripAspect = ribbon.config.length / Math.max(0.1, ribbon.config.width);
        texture.repeat.set(stripAspect / 8, 1);
    };

    const renderStaticFrame = () => {
        updateRibbonVertices(ribbon, 1.25, state);
        renderer.render(scene, camera);
    };

    resize();

    let resizePending = false;
    const ro = new ResizeObserver(() => {
        if (resizePending) return;
        resizePending = true;
        requestAnimationFrame(() => {
            resizePending = false;
            resize();
            if (prefersReducedMotion) renderStaticFrame();
        });
    });
    ro.observe(canvasHost);

    // Raycaster + tactile pointer interaction: click/tap moves ribbon, launches satin wave & cycles didactic phrase
    const raycaster = new THREE.Raycaster();
    const pointerNdc = new THREE.Vector2();
    let isDragging = false;
    let dragStartX = 0;
    let dragStartY = 0;
    let lastDragX = 0;
    let movedDistance = 0;

    const triggerDidacticPulse = (clientX, clientY) => {
        const rect = canvasHost.getBoundingClientRect();
        let hitU = rect.width ? (clientX - rect.left) / rect.width : 0.5;

        if (rect.width && rect.height && typeof clientX === 'number' && typeof clientY === 'number') {
            pointerNdc.x = ((clientX - rect.left) / rect.width) * 2 - 1;
            pointerNdc.y = -((clientY - rect.top) / rect.height) * 2 + 1;
            raycaster.setFromCamera(pointerNdc, camera);
            const hits = raycaster.intersectObject(mesh, false);
            if (hits.length && hits[0].uv) {
                hitU = hits[0].uv.x;
            }
        }

        // Launch tactile wave + spin impulse + reverse/boost scroll pulse
        state.ripple.originU = Math.min(0.95, Math.max(0.05, hitU));
        state.ripple.amp = 1.0;
        state.ripple.age = 0;
        state.twistVelocity += 5.2;
        state.flowVelocity = (state.flowVelocity >= 0 ? -0.22 : 0.22);

        // Advance didactic storefront message smoothly on click
        if (phrases.length > 1) {
            phraseIndex = (phraseIndex + 1) % phrases.length;
            paintSatinCanvas(texCanvas, { text: phrases[phraseIndex], ...palette });
            texture.needsUpdate = true;
            if (statusEl) {
                statusEl.textContent = `${phraseIndex + 1} / ${phrases.length}`;
            }
            if (hintEl) {
                hintEl.textContent = phrases[phraseIndex].split('·')[0].trim();
            }
        }

        if (prefersReducedMotion) {
            state.twistOffset += Math.PI * 0.5;
            renderStaticFrame();
        }
    };

    canvasHost.addEventListener('pointerdown', (e) => {
        isDragging = true;
        dragStartX = e.clientX;
        dragStartY = e.clientY;
        lastDragX = e.clientX;
        movedDistance = 0;
        canvasHost.setPointerCapture?.(e.pointerId);
    });

    canvasHost.addEventListener('pointermove', (e) => {
        const rect = canvasHost.getBoundingClientRect();
        if (rect.width && rect.height) {
            state.targetPtrX = ((e.clientX - rect.left) / rect.width - 0.5) * 2;
            state.targetPtrY = ((e.clientY - rect.top) / rect.height - 0.5) * 2;
        }

        if (!isDragging) return;
        const dx = e.clientX - lastDragX;
        lastDragX = e.clientX;
        movedDistance = Math.hypot(e.clientX - dragStartX, e.clientY - dragStartY);

        if (rect.width) {
            texture.offset.x = (texture.offset.x - (dx / rect.width) * 1.8) % 1;
            state.twistVelocity += (dx / rect.width) * 18;
        }
    }, { passive: true });

    const endPointer = (e) => {
        if (!isDragging) return;
        isDragging = false;
        if (movedDistance < 10) {
            triggerDidacticPulse(e.clientX, e.clientY);
        }
    };

    canvasHost.addEventListener('pointerup', endPointer);
    canvasHost.addEventListener('pointercancel', () => {
        isDragging = false;
    });
    canvasHost.addEventListener('pointerleave', () => {
        state.targetPtrX = 0;
        state.targetPtrY = 0;
    }, { passive: true });

    canvasHost.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            const rect = canvasHost.getBoundingClientRect();
            triggerDidacticPulse(rect.left + rect.width * 0.5, rect.top + rect.height * 0.5);
        }
    });

    if (prefersReducedMotion) {
        renderStaticFrame();
        return;
    }

    let rafId = 0;
    let isVisible = true;
    let lastTime = performance.now();
    let elapsed = 0;

    const animate = (now) => {
        rafId = 0;
        if (!isVisible || document.hidden) return;

        const dt = Math.min(0.05, Math.max(0.001, (now - lastTime) / 1000));
        lastTime = now;
        elapsed += dt;

        // Smooth pointer parallax
        state.ptrX += (state.targetPtrX - state.ptrX) * Math.min(1, dt * 5);
        state.ptrY += (state.targetPtrY - state.ptrY) * Math.min(1, dt * 5);

        // Relax flow speed back smoothly toward base marquee cruise velocity
        const cruiseSpeed = 0.052;
        state.flowVelocity += (cruiseSpeed - state.flowVelocity) * Math.min(1, dt * 1.4);
        texture.offset.x = (texture.offset.x - dt * state.flowVelocity) % 1;

        // Apply angular spin velocity & natural silk damping
        state.twistOffset += state.twistVelocity * dt;
        state.twistVelocity *= Math.exp(-3.2 * dt);

        // Advance and damp click ripple
        if (state.ripple.amp > 0.001) {
            state.ripple.age += dt;
            state.ripple.amp *= Math.exp(-2.6 * dt);
            if (state.ripple.amp <= 0.001) state.ripple.amp = 0;
        }

        updateRibbonVertices(ribbon, elapsed, state);

        keyLight.position.x = 2.4 + state.ptrX * 2.2;
        keyLight.position.y = 5.0 - state.ptrY * 1.4;

        renderer.render(scene, camera);
        rafId = requestAnimationFrame(animate);
    };

    const startLoop = () => {
        if (!rafId && isVisible && !document.hidden) {
            lastTime = performance.now();
            rafId = requestAnimationFrame(animate);
        }
    };

    const stopLoop = () => {
        if (rafId) {
            cancelAnimationFrame(rafId);
            rafId = 0;
        }
    };

    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                isVisible = entry.isIntersecting;
                if (isVisible) startLoop();
                else stopLoop();
            });
        }, { rootMargin: '160px 0px' });
        io.observe(stageEl);
    }

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) stopLoop();
        else startLoop();
    });

    startLoop();
}
