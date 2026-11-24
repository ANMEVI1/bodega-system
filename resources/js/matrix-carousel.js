const MATRIX_GLYPHS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ+/%•·';

/**
 * Subtle vertical Matrix rain rendered in NEXO Bodega's Peruvian Garden palette
 * (#263822 botanical green + #A44A28 terracotta highlights over warm linen).
 */
function createMatrixRain(canvas, stageEl, { prefersReducedMotion }) {
    const ctx = canvas.getContext('2d');
    if (!ctx) return { start() {}, stop() {}, drawStatic() {} };

    const chars = ['0', '1', 'S/', 'KG', '•', '+', '5', '1', '9', 'A', 'M'];
    let columns = [];
    let fontSize = 12;
    let width = 0;
    let height = 0;
    let dpr = 1;
    let rafId = 0;
    let running = false;
    let lastTick = 0;

    const setupColumns = () => {
        const rect = stageEl.getBoundingClientRect();
        width = Math.max(1, Math.floor(rect.width));
        height = Math.max(1, Math.floor(rect.height));
        dpr = Math.min(window.devicePixelRatio || 1, 1.5);

        canvas.width = Math.floor(width * dpr);
        canvas.height = Math.floor(height * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        fontSize = width < 640 ? 11 : 12;
        const stepX = 24;
        const count = Math.max(8, Math.ceil(width / stepX));

        columns = Array.from({ length: count }, (_, idx) => ({
            x: idx * stepX + 8,
            y: Math.random() * height,
            speed: 18 + Math.random() * 24,
            glyph: chars[Math.floor(Math.random() * chars.length)],
            accent: Math.random() > 0.78,
            tailLen: 4 + Math.floor(Math.random() * 5),
        }));
    };

    const renderFrame = (dt) => {
        ctx.clearRect(0, 0, width, height);
        ctx.font = `600 ${fontSize}px "JetBrains Mono", ui-monospace, monospace`;
        ctx.textAlign = 'center';

        for (let i = 0; i < columns.length; i++) {
            const col = columns[i];
            col.y += col.speed * dt;
            if (col.y - col.tailLen * 16 > height) {
                col.y = -12;
                col.glyph = chars[Math.floor(Math.random() * chars.length)];
                col.accent = Math.random() > 0.78;
            }

            if (Math.random() < dt * 2.2) {
                col.glyph = chars[Math.floor(Math.random() * chars.length)];
            }

            for (let t = 0; t < col.tailLen; t++) {
                const gy = col.y - t * 15;
                if (gy < -10 || gy > height + 10) continue;

                const fade = (1 - t / col.tailLen);
                const isHead = t === 0;
                if (col.accent) {
                    ctx.fillStyle = `rgba(164, 74, 40, ${(isHead ? 0.24 : 0.1 * fade).toFixed(3)})`;
                } else {
                    ctx.fillStyle = `rgba(38, 56, 34, ${(isHead ? 0.2 : 0.08 * fade).toFixed(3)})`;
                }

                const drawGlyph = isHead ? col.glyph : chars[(i + t + Math.floor(col.y * 0.05)) % chars.length];
                ctx.fillText(drawGlyph, col.x, gy);
            }
        }
    };

    const loop = (now) => {
        rafId = 0;
        if (!running || document.hidden) return;
        const dt = Math.min(0.05, Math.max(0.008, (now - lastTick) / 1000));
        lastTick = now;
        renderFrame(dt);
        rafId = requestAnimationFrame(loop);
    };

    setupColumns();

    let resizeQueued = false;
    const ro = new ResizeObserver(() => {
        if (resizeQueued) return;
        resizeQueued = true;
        requestAnimationFrame(() => {
            resizeQueued = false;
            setupColumns();
            if (prefersReducedMotion) renderFrame(0);
        });
    });
    ro.observe(stageEl);

    return {
        start() {
            if (prefersReducedMotion || running) return;
            running = true;
            lastTick = performance.now();
            rafId = requestAnimationFrame(loop);
        },
        stop() {
            running = false;
            if (rafId) {
                cancelAnimationFrame(rafId);
                rafId = 0;
            }
        },
        drawStatic() {
            renderFrame(0.016);
        },
    };
}

/**
 * Scramble/decode monospace pill text cleanly like a Matrix character lock-in.
 */
function runMatrixScramble(el) {
    if (!el || el.dataset.scrambling === '1') return;
    const target = el.dataset.matrixLabel || el.textContent.trim();
    el.dataset.matrixLabel = target;
    el.dataset.scrambling = '1';

    let progress = 0;
    const stepSize = Math.max(0.38, target.length / 18);

    const step = () => {
        const out = target
            .split('')
            .map((ch, idx) => {
                if (ch === ' ' || ch === '·' || ch === '/') return ch;
                if (idx < progress) return ch;
                return MATRIX_GLYPHS[Math.floor(Math.random() * MATRIX_GLYPHS.length)];
            })
            .join('');

        el.textContent = out;
        progress += stepSize;

        if (progress <= target.length) {
            requestAnimationFrame(step);
        } else {
            el.textContent = target;
            delete el.dataset.scrambling;
        }
    };

    requestAnimationFrame(step);
}

export function initMatrixCarousel(root, options = {}) {
    if (!root || root.dataset.matrixMounted === 'true') return;
    root.dataset.matrixMounted = 'true';

    const prefersReducedMotion = Boolean(options.prefersReducedMotion);
    const viewport = root.querySelector('[data-matrix-viewport]');
    const track = root.querySelector('[data-matrix-track]');
    const rainCanvas = root.querySelector('[data-matrix-rain]');
    const prevBtn = root.querySelector('[data-matrix-prev]');
    const nextBtn = root.querySelector('[data-matrix-next]');
    const toggleBtn = root.querySelector('[data-matrix-toggle]');
    const stateLabel = root.querySelector('[data-matrix-state]');
    if (!viewport || !track) return;

    const originals = Array.from(track.children);
    if (!originals.length) return;

    // Duplicate items cleanly once for true continuous zero-gap infinite wrapping
    originals.forEach((card) => {
        const clone = card.cloneNode(true);
        clone.setAttribute('aria-hidden', 'true');
        clone.querySelectorAll('a, button').forEach((el) => el.setAttribute('tabindex', '-1'));
        track.appendChild(clone);
    });

    const rain = rainCanvas ? createMatrixRain(rainCanvas, root, { prefersReducedMotion }) : null;
    if (prefersReducedMotion && rain) {
        rain.drawStatic();
    }

    let halfWidth = 0;
    let offset = 0;
    let baseSpeed = 38; // px/sec silky smooth speed
    let currentSpeed = prefersReducedMotion ? 0 : baseSpeed;
    let targetSpeed = currentSpeed;
    let isHovered = false;
    let isPaused = prefersReducedMotion;
    let isVisible = true;
    let isDragging = false;
    let dragStartX = 0;
    let dragLastX = 0;
    let dragDistance = 0;
    let boostImpulse = 0;
    let rafId = 0;
    let lastTime = performance.now();
    let nextWaveAt = performance.now() + 2600;

    const measure = () => {
        halfWidth = track.scrollWidth / 2;
        if (halfWidth > 0) {
            offset = ((offset % halfWidth) + halfWidth) % halfWidth;
        }
    };

    const syncToggleUi = () => {
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-pressed', isPaused ? 'true' : 'false');
            toggleBtn.textContent = isPaused ? 'Reanudar' : 'Pausar';
        }
        if (stateLabel) {
            stateLabel.textContent = isPaused ? 'En pausa' : 'Flujo activo';
        }
    };

    const wrapOffset = () => {
        if (halfWidth <= 0) return;
        while (offset >= halfWidth) offset -= halfWidth;
        while (offset < 0) offset += halfWidth;
    };

    const applyTransform = () => {
        track.style.transform = `translate3d(${(-offset).toFixed(2)}px, 0, 0)`;
    };

    const allCards = Array.from(track.children);
    allCards.forEach((card) => {
        const tag = card.querySelector('[data-matrix-tag]');
        if (tag && !tag.dataset.matrixLabel) {
            tag.dataset.matrixLabel = tag.textContent.trim();
        }
        card.addEventListener('mouseenter', () => {
            if (!prefersReducedMotion && tag) runMatrixScramble(tag);
        });
        card.addEventListener('click', () => {
            if (dragDistance > 8) return;
            if (!prefersReducedMotion && tag) runMatrixScramble(tag);
        });
    });

    // Smooth deceleration on hover instead of jarring abrupt freeze
    viewport.addEventListener('mouseenter', () => {
        isHovered = true;
    });
    viewport.addEventListener('mouseleave', () => {
        isHovered = false;
    });

    // Interactive pointer drag with smooth release impulse
    viewport.addEventListener('pointerdown', (e) => {
        if (e.target.closest('button, a')) return;
        isDragging = true;
        dragStartX = e.clientX;
        dragLastX = e.clientX;
        dragDistance = 0;
        viewport.setPointerCapture?.(e.pointerId);
    });

    viewport.addEventListener('pointermove', (e) => {
        if (!isDragging) return;
        const dx = e.clientX - dragLastX;
        dragLastX = e.clientX;
        dragDistance = Math.hypot(e.clientX - dragStartX, 0);
        offset -= dx;
        wrapOffset();
        applyTransform();
        boostImpulse = Math.max(-180, Math.min(180, -dx * 10));
    });

    const endDrag = () => {
        isDragging = false;
    };
    viewport.addEventListener('pointerup', endDrag);
    viewport.addEventListener('pointercancel', endDrag);

    prevBtn?.addEventListener('click', () => {
        boostImpulse -= 420;
        const randomTag = allCards[Math.floor(Math.random() * originals.length)]?.querySelector('[data-matrix-tag]');
        if (!prefersReducedMotion && randomTag) runMatrixScramble(randomTag);
    });

    nextBtn?.addEventListener('click', () => {
        boostImpulse += 420;
        const randomTag = allCards[Math.floor(Math.random() * originals.length)]?.querySelector('[data-matrix-tag]');
        if (!prefersReducedMotion && randomTag) runMatrixScramble(randomTag);
    });

    toggleBtn?.addEventListener('click', () => {
        isPaused = !isPaused;
        syncToggleUi();
        if (isPaused) rain?.stop();
        else if (isVisible) rain?.start();
    });

    const animate = (now) => {
        rafId = 0;
        if (!isVisible || document.hidden) return;

        const dt = Math.min(0.05, Math.max(0.001, (now - lastTime) / 1000));
        lastTime = now;

        if (!isDragging) {
            targetSpeed = (isPaused || isHovered) ? 0 : baseSpeed;
            currentSpeed += (targetSpeed - currentSpeed) * Math.min(1, dt * 4.5);

            const stepMove = (currentSpeed + boostImpulse) * dt;
            boostImpulse *= Math.exp(-5.2 * dt);

            if (Math.abs(stepMove) > 0.01) {
                offset += stepMove;
                wrapOffset();
                applyTransform();
            }
        }

        // Subtle periodic Matrix tag decode ripple across visible cards
        if (!isPaused && !isHovered && now >= nextWaveAt) {
            nextWaveAt = now + 3200;
            const vpRect = viewport.getBoundingClientRect();
            const visibleTags = allCards
                .filter((c) => {
                    const r = c.getBoundingClientRect();
                    return r.right > vpRect.left + 30 && r.left < vpRect.right - 30;
                })
                .map((c) => c.querySelector('[data-matrix-tag]'))
                .filter(Boolean);

            if (visibleTags.length) {
                const chosen = visibleTags[Math.floor(Math.random() * visibleTags.length)];
                runMatrixScramble(chosen);
            }
        }

        rafId = requestAnimationFrame(animate);
    };

    const startLoop = () => {
        if (!rafId && isVisible && !document.hidden) {
            lastTime = performance.now();
            rafId = requestAnimationFrame(animate);
            if (!isPaused) rain?.start();
        }
    };

    const stopLoop = () => {
        if (rafId) {
            cancelAnimationFrame(rafId);
            rafId = 0;
        }
        rain?.stop();
    };

    measure();
    const ro = new ResizeObserver(measure);
    ro.observe(track);

    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                isVisible = entry.isIntersecting;
                if (isVisible) startLoop();
                else stopLoop();
            });
        }, { rootMargin: '140px 0px' });
        io.observe(root);
    }

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) stopLoop();
        else startLoop();
    });

    syncToggleUi();
    startLoop();
}
