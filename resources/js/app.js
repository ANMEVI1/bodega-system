import './bootstrap';
import { initNiaChatbot } from './nia-chatbot';
import { initSiteChatbot } from './site-chatbot';

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* --------------------------------------------------------------------------
 * 1. Cinematic Scroll Reveals
 * -------------------------------------------------------------------------- */
const revealElements = Array.from(document.querySelectorAll('[data-reveal]'));

if (revealElements.length) {
    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        revealElements.forEach((el) => el.classList.add('is-revealed'));
    } else {
        const revealItem = (el) => {
            if (!el.classList.contains('is-revealed')) {
                el.classList.add('is-revealed');
            }
        };

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        revealItem(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.06, rootMargin: '0px 0px -30px 0px' }
        );

        revealElements.forEach((el) => {
            const rect = el.getBoundingClientRect();
            if (rect.top < window.innerHeight * 0.95) {
                requestAnimationFrame(() => revealItem(el));
            } else {
                observer.observe(el);
            }
        });

        // Safety fallback so scrolled/printed/fast views never leave items hidden
        window.addEventListener('scroll', () => {
            revealElements.forEach((el) => {
                if (!el.classList.contains('is-revealed')) {
                    const rect = el.getBoundingClientRect();
                    if (rect.top <= window.innerHeight + 60) {
                        revealItem(el);
                        observer.unobserve(el);
                    }
                }
            });
        }, { passive: true });
    }
}

/* --------------------------------------------------------------------------
 * 2. Animated Financial & Metric Count-Up
 * -------------------------------------------------------------------------- */
function formatNumberWithCommas(value, decimals) {
    const fixed = Number(value).toFixed(decimals);
    const [intPart, decPart] = fixed.split('.');
    const withCommas = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return decimals > 0 ? `${withCommas}.${decPart}` : withCommas;
}

document.querySelectorAll('[data-countup]').forEach((el) => {
    const rawTarget = parseFloat(el.dataset.countup || '0');
    const prefix = el.dataset.prefix || '';
    const suffix = el.dataset.suffix || '';
    const decimals = parseInt(el.dataset.decimals || '0', 10);

    if (prefersReducedMotion || Number.isNaN(rawTarget) || rawTarget === 0) {
        el.textContent = `${prefix}${formatNumberWithCommas(rawTarget || 0, decimals)}${suffix}`;
        return;
    }

    const duration = 900;
    const start = performance.now();

    function tick(now) {
        const elapsed = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - elapsed, 3.2);
        const current = rawTarget * eased;
        el.textContent = `${prefix}${formatNumberWithCommas(current, decimals)}${suffix}`;

        if (elapsed < 1) {
            requestAnimationFrame(tick);
        } else {
            el.textContent = `${prefix}${formatNumberWithCommas(rawTarget, decimals)}${suffix}`;
        }
    }

    requestAnimationFrame(tick);
});

document.querySelectorAll('[data-bar-height]').forEach((bar) => {
    const height = parseFloat(bar.dataset.barHeight || '6');
    if (!Number.isNaN(height)) {
        bar.style.height = `${Math.min(100, Math.max(6, height))}%`;
    }
});

/* --------------------------------------------------------------------------
 * 3. Pointer Spotlight Tracking on Cards
 * -------------------------------------------------------------------------- */
if (!prefersReducedMotion) {
    document.addEventListener('pointermove', (event) => {
        const card = event.target.closest('.spotlight-card');
        if (!card) return;
        const rect = card.getBoundingClientRect();
        card.style.setProperty('--mx', `${event.clientX - rect.left}px`);
        card.style.setProperty('--my', `${event.clientY - rect.top}px`);
    }, { passive: true });
}

/* --------------------------------------------------------------------------
 * 4. 3D Perspective Hero Showcase Stage
 * -------------------------------------------------------------------------- */
document.querySelectorAll('.tilt-stage').forEach((stage) => {
    if (prefersReducedMotion || window.matchMedia('(pointer: coarse)').matches) return;

    let targetRx = 0;
    let targetRy = 0;
    let currentRx = 0;
    let currentRy = 0;
    let rafId = null;

    const animate = () => {
        currentRx += (targetRx - currentRx) * 0.12;
        currentRy += (targetRy - currentRy) * 0.12;
        stage.style.setProperty('--rx', `${currentRx.toFixed(2)}deg`);
        stage.style.setProperty('--ry', `${currentRy.toFixed(2)}deg`);

        if (Math.abs(targetRx - currentRx) > 0.02 || Math.abs(targetRy - currentRy) > 0.02) {
            rafId = requestAnimationFrame(animate);
        } else {
            rafId = null;
        }
    };

    stage.addEventListener('pointermove', (e) => {
        const rect = stage.getBoundingClientRect();
        const x = (e.clientX - rect.left) / rect.width;
        const y = (e.clientY - rect.top) / rect.height;
        targetRy = (x - 0.5) * 9;
        targetRx = (0.5 - y) * 9;
        stage.style.setProperty('--gx', `${(x * 100).toFixed(1)}%`);
        stage.style.setProperty('--gy', `${(y * 100).toFixed(1)}%`);
        if (!rafId) rafId = requestAnimationFrame(animate);
    });

    stage.addEventListener('pointerleave', () => {
        targetRx = 0;
        targetRy = 0;
        if (!rafId) rafId = requestAnimationFrame(animate);
    });
});

/* --------------------------------------------------------------------------
 * 4b. Interactive Movable Peruvian Bodega Stage & Scroll Parallax
 * -------------------------------------------------------------------------- */
document.querySelectorAll('[data-hero-scene]').forEach((hero) => {
    if (prefersReducedMotion || window.matchMedia('(pointer: coarse)').matches) return;
    const backdropImg = hero.querySelector('[data-hero-backdrop-img]');

    hero.addEventListener('pointermove', (e) => {
        const rect = hero.getBoundingClientRect();
        const nx = (e.clientX - rect.left) / rect.width;
        const ny = (e.clientY - rect.top) / rect.height;
        hero.style.setProperty('--hero-mx', `${(nx * 100).toFixed(1)}%`);
        hero.style.setProperty('--hero-my', `${(ny * 100).toFixed(1)}%`);
        if (backdropImg) {
            backdropImg.style.setProperty('--bg-px', `${((0.5 - nx) * 2.4).toFixed(2)}%`);
            backdropImg.style.setProperty('--bg-py', `${((0.5 - ny) * 1.8).toFixed(2)}%`);
        }
    }, { passive: true });
});

document.querySelectorAll('[data-bodega-stage]').forEach((stageEl) => {
    const viewport = stageEl.querySelector('[data-bodega-viewport]');
    const media = stageEl.querySelector('[data-bodega-img]');
    const hudNodes = stageEl.querySelectorAll('[data-bodega-hud]');
    const focusBtns = stageEl.querySelectorAll('[data-bodega-focus]');
    const hintLabel = stageEl.querySelector('[data-bodega-hint]');
    if (!viewport || !media) return;

    const is3dReady = () => stageEl.dataset.mode3d === 'ready';

    // Code-split Three.js loader only on pages containing the bodega stage
    if (viewport.dataset.modelUrl) {
        import('./bodega-3d')
            .then(({ initBodega3DViewer }) => {
                initBodega3DViewer(stageEl, { prefersReducedMotion });
            })
            .catch(() => {
                stageEl.querySelector('[data-bodega-loading]')?.classList.add('hidden');
            });
    }

    const presets = {
        general: { x: 0, y: 0, scale: 1.14, hint: 'Mueve o arrastra la escena' },
        estantes: { x: 8.5, y: 1.2, scale: 1.28, hint: 'Estantería y granos andinos' },
        frescos: { x: -2.2, y: -4.2, scale: 1.26, hint: 'Frutas frescas y productos peruanos' },
        mostrador: { x: -7.5, y: -6.2, scale: 1.28, hint: 'Pan francés y mostrador del día' },
    };

    let activeFocus = 'general';
    let panX = 0;
    let panY = 0;
    let panScale = 1.14;
    let isDragging = false;
    let dragStartX = 0;
    let dragStartY = 0;
    let originPanX = 0;
    let originPanY = 0;

    const clamp = (val, min, max) => Math.min(max, Math.max(min, val));

    const applyTransform = (x, y, scale, hudX = 0, hudY = 0) => {
        media.style.setProperty('--pan-x', `${x.toFixed(2)}%`);
        media.style.setProperty('--pan-y', `${y.toFixed(2)}%`);
        media.style.setProperty('--pan-scale', scale.toFixed(2));
        hudNodes.forEach((node) => {
            node.style.setProperty('--hud-x', `${hudX.toFixed(1)}px`);
            node.style.setProperty('--hud-y', `${hudY.toFixed(1)}px`);
        });
    };

    const setActivePresetButton = (key) => {
        focusBtns.forEach((btn) => {
            const active = btn.dataset.bodegaFocus === key;
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    };

    const goToPreset = (key) => {
        if (is3dReady()) return;
        const preset = presets[key] || presets.general;
        activeFocus = key;
        panX = preset.x;
        panY = preset.y;
        panScale = preset.scale;
        media.classList.toggle('is-idle', key === 'general' && !prefersReducedMotion);
        applyTransform(panX, panY, panScale, -panX * 0.45, -panY * 0.45);
        setActivePresetButton(key);
        if (hintLabel) hintLabel.textContent = preset.hint;
    };

    focusBtns.forEach((btn) => {
        btn.addEventListener('click', (e) => {
            if (is3dReady()) return;
            e.stopPropagation();
            goToPreset(btn.dataset.bodegaFocus || 'general');
        });
    });

    if (!prefersReducedMotion) {
        viewport.addEventListener('pointerdown', (e) => {
            if (is3dReady() || e.target.closest('button')) return;
            isDragging = true;
            dragStartX = e.clientX;
            dragStartY = e.clientY;
            originPanX = panX;
            originPanY = panY;
            viewport.classList.add('is-dragging');
            media.classList.remove('is-idle');
            viewport.setPointerCapture?.(e.pointerId);
        });

        viewport.addEventListener('pointermove', (e) => {
            if (is3dReady()) return;
            const rect = viewport.getBoundingClientRect();
            if (isDragging) {
                const dxPct = ((e.clientX - dragStartX) / Math.max(rect.width, 1)) * 22;
                const dyPct = ((e.clientY - dragStartY) / Math.max(rect.height, 1)) * 18;
                panX = clamp(originPanX + dxPct, -10.5, 10.5);
                panY = clamp(originPanY + dyPct, -8, 8);
                panScale = 1.24;
                applyTransform(panX, panY, panScale, -panX * 0.4, -panY * 0.4);
                if (hintLabel) hintLabel.textContent = 'Explorando bodega';
                return;
            }

            if (e.pointerType !== 'mouse' || activeFocus !== 'general') return;
            const nx = (e.clientX - rect.left) / rect.width - 0.5;
            const ny = (e.clientY - rect.top) / rect.height - 0.5;
            viewport.classList.add('is-pointer-active');
            media.classList.remove('is-idle');
            panX = clamp(-nx * 13, -7.5, 7.5);
            panY = clamp(-ny * 10, -5.5, 5.5);
            panScale = 1.18;
            applyTransform(panX, panY, panScale, nx * 8, ny * 6);
        });

        const endDrag = (e) => {
            if (!isDragging) return;
            isDragging = false;
            viewport.classList.remove('is-dragging');
            viewport.releasePointerCapture?.(e.pointerId);
        };

        viewport.addEventListener('pointerup', endDrag);
        viewport.addEventListener('pointercancel', endDrag);

        viewport.addEventListener('pointerleave', () => {
            if (is3dReady()) return;
            viewport.classList.remove('is-pointer-active');
            if (!isDragging && activeFocus === 'general') {
                goToPreset('general');
            }
        });

        viewport.addEventListener('dblclick', (e) => {
            if (is3dReady() || e.target.closest('button')) return;
            goToPreset('general');
        });
    }
});

const parallaxItems = Array.from(document.querySelectorAll('[data-parallax-y]'));
if (parallaxItems.length && !prefersReducedMotion) {
    let parallaxTicking = false;
    const updateParallax = () => {
        const y = window.scrollY;
        parallaxItems.forEach((el) => {
            const factor = parseFloat(el.dataset.parallaxY || '0.12');
            const clampedOffset = Math.min(90, Math.max(-45, y * factor));
            el.style.transform = `translate3d(0, ${clampedOffset.toFixed(1)}px, 0)`;
        });
        parallaxTicking = false;
    };

    window.addEventListener('scroll', () => {
        if (!parallaxTicking) {
            parallaxTicking = true;
            requestAnimationFrame(updateParallax);
        }
    }, { passive: true });
    updateParallax();
}

/* --------------------------------------------------------------------------
 * 5. Scroll Progress Bar (Public Site)
 * -------------------------------------------------------------------------- */
const scrollProgress = document.getElementById('scroll-progress');
if (scrollProgress) {
    const updateProgress = () => {
        const scrollTop = window.scrollY;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const ratio = docHeight > 0 ? Math.min(Math.max(scrollTop / docHeight, 0), 1) : 0;
        scrollProgress.style.transform = `scaleX(${ratio})`;
    };
    window.addEventListener('scroll', updateProgress, { passive: true });
    updateProgress();
}

/* --------------------------------------------------------------------------
 * 6. Public Storefront Category Filter & Quick Product Inquiry
 * -------------------------------------------------------------------------- */
const filterButtons = document.querySelectorAll('[data-category-filter]');
const productCards = document.querySelectorAll('[data-product-card]');

if (filterButtons.length && productCards.length) {
    filterButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const selected = btn.dataset.categoryFilter;

            filterButtons.forEach((b) => {
                const active = b === btn;
                b.setAttribute('aria-pressed', active ? 'true' : 'false');
            });

            productCards.forEach((card) => {
                const match = selected === 'all' || card.dataset.category === selected;
                if (match) {
                    card.classList.remove('hidden');
                    requestAnimationFrame(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'scale(1)';
                    });
                } else {
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.96)';
                    card.classList.add('hidden');
                }
            });
        });
    });
}

document.querySelectorAll('[data-inquire-product]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const productName = btn.dataset.inquireProduct;
        const contactForm = document.getElementById('contacto');
        const messageField = contactForm?.querySelector('textarea[name="message"]');
        if (messageField) {
            messageField.value = `Hola, quisiera consultar disponibilidad y precio por "${productName}".`;
            messageField.focus({ preventScroll: true });
        }
        if (contactForm) {
            const top = contactForm.getBoundingClientRect().top + window.scrollY - 76;
            window.scrollTo({ top: Math.max(0, top), behavior: prefersReducedMotion ? 'auto' : 'smooth' });
        }
    });
});

/* --------------------------------------------------------------------------
 * 7. Interactive FAQ Accordion
 * -------------------------------------------------------------------------- */
document.querySelectorAll('[data-faq-trigger]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
        const item = trigger.closest('.faq-item');
        if (!item) return;
        const isOpen = item.dataset.open === 'true';

        // Close siblings smoothly
        document.querySelectorAll('.faq-item').forEach((other) => {
            other.dataset.open = 'false';
            other.querySelector('[data-faq-trigger]')?.setAttribute('aria-expanded', 'false');
        });

        item.dataset.open = isOpen ? 'false' : 'true';
        trigger.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
    });
});

/* --------------------------------------------------------------------------
 * 8. NIA Local Assistant & Storefront Client Chatbot
 * -------------------------------------------------------------------------- */
const bootChatbots = () => {
    initNiaChatbot();
    initSiteChatbot();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootChatbots);
} else {
    bootChatbots();
}

/* --------------------------------------------------------------------------
 * 9. Live Product Pricing Preview Calculator
 * -------------------------------------------------------------------------- */
const priceCalcForm = document.querySelector('[data-price-calc]');
if (priceCalcForm) {
    const costInput = priceCalcForm.querySelector('[name="cost"]');
    const expenseInput = priceCalcForm.querySelector('[name="unit_expense"]');
    const marginInput = priceCalcForm.querySelector('[name="margin"]');
    const outPrice = document.getElementById('calc-selling-price');
    const outCost = document.getElementById('calc-total-cost');
    const outProfit = document.getElementById('calc-unit-profit');
    const outBar = document.getElementById('calc-margin-bar');

    const updatePriceCalc = () => {
        const cost = Math.max(0, parseFloat(costInput?.value || '0') || 0);
        const expense = Math.max(0, parseFloat(expenseInput?.value || '0') || 0);
        const margin = Math.min(95, Math.max(0, parseFloat(marginInput?.value || '0') || 0));
        const totalCost = cost + expense;
        const suggested = margin < 100 ? totalCost / (1 - margin / 100) : totalCost;
        const unitProfit = suggested - totalCost;

        if (outCost) outCost.textContent = `S/ ${totalCost.toFixed(2)}`;
        if (outPrice) outPrice.textContent = `S/ ${suggested.toFixed(2)}`;
        if (outProfit) outProfit.textContent = `S/ ${unitProfit.toFixed(2)}`;
        if (outBar) outBar.style.width = `${Math.min(100, Math.max(4, margin))}%`;
    };

    [costInput, expenseInput, marginInput].forEach((el) => el?.addEventListener('input', updatePriceCalc));
    updatePriceCalc();
}

/* --------------------------------------------------------------------------
 * 10. Live Sale Preview Calculator
 * -------------------------------------------------------------------------- */
const saleCalcForm = document.querySelector('[data-sale-calc]');
if (saleCalcForm) {
    const productSelect = saleCalcForm.querySelector('[name="product_id"]');
    const qtyInput = saleCalcForm.querySelector('[name="quantity"]');
    const priceInput = saleCalcForm.querySelector('[name="unit_price"]');
    const outTotal = document.getElementById('sale-preview-total');
    const outStock = document.getElementById('sale-preview-stock');
    const outWarning = document.getElementById('sale-stock-warning');

    const updateSaleCalc = () => {
        const opt = productSelect?.selectedOptions?.[0];
        if (!opt) return;
        const basePrice = parseFloat(opt.dataset.price || '0') || 0;
        const stock = parseFloat(opt.dataset.stock || '0') || 0;
        const unit = opt.dataset.unit || 'u';
        const qty = Math.max(0, parseFloat(qtyInput?.value || '0') || 0);
        const overridePrice = priceInput?.value !== '' ? parseFloat(priceInput.value) : basePrice;
        const unitPrice = Number.isNaN(overridePrice) ? basePrice : Math.max(0, overridePrice);

        if (outTotal) outTotal.textContent = `S/ ${(qty * unitPrice).toFixed(2)}`;
        if (outStock) outStock.textContent = `${stock.toFixed(0)} ${unit}`;
        if (outWarning) {
            const exceeds = qty > stock && qty > 0;
            outWarning.classList.toggle('hidden', !exceeds);
        }
    };

    [productSelect, qtyInput, priceInput].forEach((el) => {
        el?.addEventListener('input', updateSaleCalc);
        el?.addEventListener('change', updateSaleCalc);
    });
    updateSaleCalc();
}

/* --------------------------------------------------------------------------
 * 11. Password Toggle & Strength Indicator
 * -------------------------------------------------------------------------- */
document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const wrapper = btn.closest('[data-password-field]');
        const input = wrapper?.querySelector('input');
        if (!input) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.querySelector('[data-eye-open]')?.classList.toggle('hidden', show);
        btn.querySelector('[data-eye-closed]')?.classList.toggle('hidden', !show);
    });
});

document.querySelectorAll('[data-password-strength]').forEach((input) => {
    const meter = document.getElementById(input.dataset.passwordStrength);
    if (!meter) return;
    const bar = meter.querySelector('[data-strength-bar]');
    const label = meter.querySelector('[data-strength-label]');

    const evaluate = () => {
        const val = input.value || '';
        const hasLen = val.length >= 12;
        const hasMixed = /[a-z]/.test(val) && /[A-Z]/.test(val);
        const hasNum = /\d/.test(val);
        const hasSym = /[^A-Za-z0-9]/.test(val);

        let score = 0;
        if (hasLen) score++;
        if (hasMixed) score++;
        if (hasNum) score++;
        if (hasSym) score++;

        const widths = ['0%', '28%', '55%', '82%', '100%'];
        const colors = ['bg-zinc-700', 'bg-rose-500', 'bg-amber-400', 'bg-cyan-400', 'bg-emerald-400'];
        const texts = ['Mínimo 12 caracteres, mayúsculas, minúsculas y números', 'Falta completar requisitos', 'Casi lista', 'Segura', 'Muy segura'];

        if (bar) {
            bar.className = `h-full rounded-full transition-all duration-300 ${colors[val ? score : 0]}`;
            bar.style.width = val ? widths[Math.max(1, score)] : '0%';
        }
        if (label) {
            label.textContent = val ? texts[Math.max(1, score)] : texts[0];
        }

        const toggleRule = (attr, ok) => {
            const el = meter.querySelector(`[${attr}]`);
            if (!el) return;
            el.classList.toggle('text-emerald-300', ok);
            el.classList.toggle('border-emerald-400/30', ok);
            el.classList.toggle('bg-emerald-400/10', ok);
            el.classList.toggle('text-zinc-500', !ok);
            el.classList.toggle('border-white/10', !ok);
            el.classList.toggle('bg-white/[.02]', !ok);
        };
        toggleRule('data-rule-len', hasLen);
        toggleRule('data-rule-mixed', hasMixed);
        toggleRule('data-rule-num', hasNum);
    };

    input.addEventListener('input', evaluate);
});

/* --------------------------------------------------------------------------
 * 12. Custom Cinematic Confirm Modal (Replaces native window.confirm)
 * -------------------------------------------------------------------------- */
const confirmModal = document.getElementById('cinema-confirm-modal');
if (confirmModal) {
    const modalTitle = confirmModal.querySelector('[data-confirm-title]');
    const modalBody = confirmModal.querySelector('[data-confirm-body]');
    const confirmBtn = confirmModal.querySelector('[data-confirm-accept]');
    const cancelBtns = confirmModal.querySelectorAll('[data-confirm-cancel]');
    let pendingForm = null;

    const closeModal = () => {
        confirmModal.classList.add('hidden');
        confirmModal.classList.remove('flex');
        pendingForm = null;
    };

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (form.dataset.confirmed === 'true') return;
            e.preventDefault();
            pendingForm = form;
            if (modalTitle) modalTitle.textContent = form.dataset.confirmTitle || 'Confirmar acción';
            if (modalBody) modalBody.textContent = form.dataset.confirm || '¿Deseas continuar con esta acción?';
            confirmModal.classList.remove('hidden');
            confirmModal.classList.add('flex');
            confirmBtn?.focus();
        });
    });

    confirmBtn?.addEventListener('click', () => {
        if (!pendingForm) return;
        pendingForm.dataset.confirmed = 'true';
        pendingForm.submit();
        closeModal();
    });

    cancelBtns.forEach((btn) => btn.addEventListener('click', closeModal));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !confirmModal.classList.contains('hidden')) closeModal();
    });
}

/* --------------------------------------------------------------------------
 * 13. Mobile Navigation Drawer & Dismissible Toasts
 * -------------------------------------------------------------------------- */
const mobileDrawer = document.querySelector('[data-mobile-drawer]');
document.querySelectorAll('[data-drawer-open]').forEach((btn) => {
    btn.addEventListener('click', () => {
        mobileDrawer?.classList.remove('hidden');
        mobileDrawer?.classList.add('flex');
    });
});
document.querySelectorAll('[data-drawer-close]').forEach((btn) => {
    btn.addEventListener('click', () => {
        mobileDrawer?.classList.add('hidden');
        mobileDrawer?.classList.remove('flex');
    });
});

document.querySelectorAll('[data-toast]').forEach((toast) => {
    const close = () => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-8px)';
        setTimeout(() => toast.remove(), 320);
    };
    toast.querySelector('[data-toast-close]')?.addEventListener('click', close);
    setTimeout(close, 5600);
});

/* --------------------------------------------------------------------------
 * 14. Dynamic Site Editor & Real-Time Storefront Preview
 * -------------------------------------------------------------------------- */
const siteEditor = document.querySelector('[data-site-editor]');
if (siteEditor) {
    const form = document.getElementById('site-editor-form');
    const defaults = (() => {
        try {
            return JSON.parse(siteEditor.dataset.defaults || '{}');
        } catch {
            return {};
        }
    })();

    const tabButtons = Array.from(siteEditor.querySelectorAll('[data-section-tab]'));
    const sectionCards = Array.from(siteEditor.querySelectorAll('[data-section-card]'));
    const dirtyBadge = siteEditor.querySelector('[data-editor-dirty-badge]');
    const dirtyDot = siteEditor.querySelector('[data-dirty-dot]');
    const dirtyText = siteEditor.querySelector('[data-dirty-text]');
    const activeCountNode = document.querySelector('[data-preview-active-count]');
    const waTopBadge = document.querySelector('[data-preview-wa-badge]');
    const waTestLink = siteEditor.querySelector('[data-wa-test-link]');
    const waHelper = siteEditor.querySelector('[data-wa-helper]');
    const previewContainer = siteEditor.querySelector('[data-site-preview]');

    let isDirty = false;
    let isSubmitting = false;

    const markDirty = (dirty) => {
        isDirty = dirty;
        if (!dirtyBadge || !dirtyDot || !dirtyText) return;
        dirtyText.textContent = dirty ? 'Cambios sin guardar' : 'Sincronizado';
        dirtyDot.classList.toggle('bg-amber-400', dirty);
        dirtyDot.classList.toggle('bg-emerald-400', !dirty);
        dirtyBadge.classList.toggle('border-amber-400/35', dirty);
        dirtyBadge.classList.toggle('text-amber-200', dirty);
        dirtyBadge.classList.toggle('border-white/10', !dirty);
        dirtyBadge.classList.toggle('text-zinc-400', !dirty);
    };

    const getField = (section, role) =>
        siteEditor.querySelector(`[data-section-input="${section}"][data-field-role="${role}"]`);

    const getSectionState = (section) => {
        const activeEl = getField(section, 'active');
        const titleEl = getField(section, 'title');
        const contentEl = getField(section, 'content');
        const ctaLabelEl = getField(section, 'cta_label');
        const ctaUrlEl = getField(section, 'cta_url');
        const waEl = getField(section, 'whatsapp');

        return {
            active: activeEl ? activeEl.checked : true,
            title: titleEl ? titleEl.value.trim() : '',
            content: contentEl ? contentEl.value : '',
            ctaLabel: ctaLabelEl ? ctaLabelEl.value.trim() : '',
            ctaUrl: ctaUrlEl ? ctaUrlEl.value.trim() : '',
            whatsapp: waEl ? waEl.value.replace(/\D+/g, '') : '',
        };
    };

    const parseFaqLines = (raw) =>
        String(raw || '')
            .split(/\r\n|\r|\n/)
            .map((line) => line.trim())
            .filter(Boolean)
            .map((line) => {
                const match = line.match(/^((?:¿)?[^?]+\?)\s*(.+)$/);
                if (match) {
                    return { q: match[1].trim(), a: match[2].trim() };
                }
                return { q: line.endsWith('?') ? line : `${line}?`, a: '' };
            });

    const serializeFaqPairs = (pairs) =>
        pairs
            .map(({ q, a }) => {
                const cleanQ = q.replace(/[\r\n]+/g, ' ').trim();
                const cleanA = a.replace(/[\r\n]+/g, ' ').trim();
                if (!cleanQ && !cleanA) return '';
                const formattedQ = cleanQ.endsWith('?') ? cleanQ : `${cleanQ}?`;
                return cleanA ? `${formattedQ} ${cleanA}` : formattedQ;
            })
            .filter(Boolean)
            .join('\n');

    const updateCharCounters = () => {
        siteEditor.querySelectorAll('[data-char-target]').forEach((input) => {
            const counter = siteEditor.querySelector(`[data-char-count="${input.dataset.charTarget}"]`);
            if (!counter) return;
            const max = input.getAttribute('maxlength') || '';
            counter.textContent = max ? `${input.value.length}/${max}` : String(input.value.length);
        });
    };

    const renderFaqPreview = (rawFaq, isActive) => {
        const list = previewContainer?.querySelector('[data-pv-faq-list]');
        if (!list) return;
        list.replaceChildren();
        if (!isActive) return;

        const items = parseFaqLines(rawFaq);
        if (!items.length) {
            const empty = document.createElement('p');
            empty.className = 'py-2 text-[10px] text-[#595548]';
            empty.textContent = 'Agrega preguntas frecuentes para mostrarlas aquí.';
            list.appendChild(empty);
            return;
        }

        items.forEach((item, index) => {
            const row = document.createElement('div');
            row.className = 'py-2';

            const qEl = document.createElement('p');
            qEl.className = 'font-serif text-xs font-medium text-[#192315]';
            qEl.textContent = item.q;
            row.appendChild(qEl);

            if (index === 0 && item.a) {
                const aEl = document.createElement('p');
                aEl.className = 'mt-1 text-[10px] leading-4 text-[#4C493E]';
                aEl.textContent = item.a;
                row.appendChild(aEl);
            }
            list.appendChild(row);
        });
    };

    const syncPreview = () => {
        const home = getSectionState('home');
        const banner = getSectionState('banner');
        const about = getSectionState('about');
        const faq = getSectionState('faq');
        const contact = getSectionState('contact');

        const activeTotal = [home, banner, about, faq, contact].filter((s) => s.active).length;
        if (activeCountNode) activeCountNode.textContent = String(activeTotal);

        ['home', 'banner', 'about', 'faq', 'contact'].forEach((key) => {
            const state = { home, banner, about, faq, contact }[key];
            const label = siteEditor.querySelector(`[data-visibility-label="${key}"]`);
            if (label) {
                label.textContent = state.active ? 'Visible en el sitio' : 'Oculto';
                label.classList.toggle('text-emerald-300', state.active);
                label.classList.toggle('text-zinc-400', !state.active);
            }
        });

        const waValid = contact.whatsapp.length >= 8 && contact.whatsapp.length <= 15;
        if (waTopBadge) {
            waTopBadge.textContent = waValid ? `+${contact.whatsapp}` : 'Sin configurar';
            waTopBadge.classList.toggle('text-emerald-300', waValid);
            waTopBadge.classList.toggle('text-amber-300', !waValid);
        }
        if (waTestLink) {
            waTestLink.href = waValid ? `https://wa.me/${contact.whatsapp}` : '#';
            waTestLink.classList.toggle('pointer-events-none', !waValid);
            waTestLink.classList.toggle('opacity-40', !waValid);
        }
        if (waHelper && getField('contact', 'whatsapp')) {
            const rawVal = getField('contact', 'whatsapp').value.trim();
            if (rawVal && !waValid) {
                waHelper.textContent = 'Ingresa entre 8 y 15 dígitos válidos con código de país (ej. 51987654321).';
                waHelper.classList.add('text-amber-300');
                waHelper.classList.remove('text-zinc-400');
            } else if (waValid) {
                waHelper.textContent = `Listo: se guardará como ${contact.whatsapp} (enlace directo wa.me/${contact.whatsapp}).`;
                waHelper.classList.remove('text-amber-300');
                waHelper.classList.add('text-zinc-400');
            } else {
                waHelper.textContent = 'Si queda vacío, el formulario solo registra la consulta en Consultas web sin abrir WhatsApp.';
                waHelper.classList.remove('text-amber-300');
                waHelper.classList.add('text-zinc-400');
            }
        }

        if (!previewContainer) return;

        // Header WhatsApp button
        const pvHeaderWa = previewContainer.querySelector('[data-pv-header-wa]');
        if (pvHeaderWa) pvHeaderWa.textContent = waValid ? 'WhatsApp' : 'Contacto';

        // Hero & Banner
        const heroAnyActive = home.active || banner.active;
        const effectiveHero = home.active ? home : banner;
        const pvHeroEmpty = previewContainer.querySelector('[data-pv-hero-empty]');
        const pvHeroGrid = previewContainer.querySelector('[data-pv-hero-grid]');
        pvHeroEmpty?.classList.toggle('hidden', heroAnyActive);
        pvHeroGrid?.classList.toggle('hidden', !heroAnyActive);

        if (heroAnyActive) {
            const pvHeroTitle = previewContainer.querySelector('[data-pv-hero-title]');
            const pvHeroContent = previewContainer.querySelector('[data-pv-hero-content]');
            const pvHeroCta = previewContainer.querySelector('[data-pv-hero-cta]');
            const pvHeroUrl = previewContainer.querySelector('[data-pv-hero-url]');
            const pvBannerBox = previewContainer.querySelector('[data-pv-banner-box]');

            if (pvHeroTitle) pvHeroTitle.textContent = effectiveHero.title || 'Sin título';
            if (pvHeroContent) pvHeroContent.textContent = effectiveHero.content || '';
            if (pvHeroCta) pvHeroCta.textContent = effectiveHero.ctaLabel || 'Explorar catálogo';
            if (pvHeroUrl) pvHeroUrl.textContent = `→ ${effectiveHero.ctaUrl || '#productos'}`;

            pvBannerBox?.classList.toggle('hidden', !banner.active);
            pvHeroGrid?.classList.toggle('sm:grid-cols-2', banner.active);

            if (banner.active) {
                const pvBannerTitle = previewContainer.querySelector('[data-pv-banner-title]');
                const pvBannerContent = previewContainer.querySelector('[data-pv-banner-content]');
                const pvBannerCtaWrap = previewContainer.querySelector('[data-pv-banner-cta-wrap]');
                const pvBannerCta = previewContainer.querySelector('[data-pv-banner-cta]');

                if (pvBannerTitle) pvBannerTitle.textContent = banner.title || 'Fresco. Cerca. Para ti.';
                if (pvBannerContent) pvBannerContent.textContent = banner.content || '';
                const showBannerCta = home.active && banner.ctaLabel.length > 0;
                pvBannerCtaWrap?.classList.toggle('hidden', !showBannerCta);
                if (pvBannerCta && showBannerCta) {
                    pvBannerCta.textContent = `${banner.ctaLabel} →`;
                }
            }
        }

        // Toggle visibility helper for About / FAQ / Contact
        const syncBlock = (key, state) => {
            previewContainer.querySelector(`[data-pv-section-off="${key}"]`)?.classList.toggle('hidden', state.active);
            previewContainer.querySelector(`[data-pv-section-on="${key}"]`)?.classList.toggle('hidden', !state.active);
        };

        syncBlock('about', about);
        const pvAboutTitle = previewContainer.querySelector('[data-pv-about-title]');
        const pvAboutContent = previewContainer.querySelector('[data-pv-about-content]');
        if (pvAboutTitle) pvAboutTitle.textContent = about.title || 'Sobre nosotros';
        if (pvAboutContent) pvAboutContent.textContent = about.content || '';

        syncBlock('faq', faq);
        const pvFaqTitle = previewContainer.querySelector('[data-pv-faq-title]');
        if (pvFaqTitle) pvFaqTitle.textContent = faq.title || 'Preguntas frecuentes';
        renderFaqPreview(faq.content, faq.active);

        syncBlock('contact', contact);
        const pvContactTitle = previewContainer.querySelector('[data-pv-contact-title]');
        const pvContactContent = previewContainer.querySelector('[data-pv-contact-content]');
        const pvContactCta = previewContainer.querySelector('[data-pv-contact-cta]');
        const pvWaPill = previewContainer.querySelector('[data-pv-wa-pill]');
        const pvWaNumber = previewContainer.querySelector('[data-pv-wa-number]');

        if (pvContactTitle) pvContactTitle.textContent = contact.title || '¿Qué necesitas hoy?';
        if (pvContactContent) pvContactContent.textContent = contact.content || '';
        if (pvContactCta) pvContactCta.textContent = contact.ctaLabel || 'Enviar consulta';
        pvWaPill?.classList.toggle('hidden', !waValid);
        if (pvWaNumber && waValid) pvWaNumber.textContent = `+${contact.whatsapp}`;
    };

    // Structured FAQ Builder synchronized 1:1 with sections[faq][content]
    const faqTextarea = getField('faq', 'content');
    const faqBuilder = siteEditor.querySelector('[data-faq-builder]');
    const faqAddBtn = siteEditor.querySelector('[data-faq-add]');
    const faqToggleRawBtn = siteEditor.querySelector('[data-faq-toggle-raw]');
    const faqRawWrap = siteEditor.querySelector('[data-faq-raw-wrap]');

    let faqPairs = faqTextarea ? parseFaqLines(faqTextarea.value) : [];
    if (!faqPairs.length && faqBuilder) {
        faqPairs = [{ q: '¿Cuál es su horario?', a: 'Atendemos todos los días.' }];
    }

    const syncFaqTextareaFromBuilder = () => {
        if (!faqTextarea) return;
        faqTextarea.value = serializeFaqPairs(faqPairs);
        syncPreview();
    };

    const renderFaqBuilder = () => {
        if (!faqBuilder) return;
        faqBuilder.replaceChildren();

        if (!faqPairs.length) {
            const empty = document.createElement('div');
            empty.className = 'rounded-2xl border border-dashed border-white/15 bg-white/[.02] px-4 py-4 text-center text-xs text-zinc-400';
            empty.textContent = 'No hay preguntas agregadas. Usa «Agregar pregunta» para crear el acordeón.';
            faqBuilder.appendChild(empty);
            return;
        }

        faqPairs.forEach((pair, idx) => {
            const card = document.createElement('div');
            card.className = 'rounded-2xl border border-white/10 bg-white/[.025] p-3.5 space-y-2.5';

            const top = document.createElement('div');
            top.className = 'flex items-center justify-between gap-2';

            const num = document.createElement('span');
            num.className = 'font-mono-num text-[10px] font-bold uppercase tracking-wider text-fuchsia-300';
            num.textContent = `Pregunta ${String(idx + 1).padStart(2, '0')}`;
            top.appendChild(num);

            const actions = document.createElement('div');
            actions.className = 'flex items-center gap-1';

            const mkBtn = (label, disabled, title, handler) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'rounded-lg border border-white/10 bg-white/[.03] px-2 py-0.5 text-[10px] font-bold text-zinc-300 transition hover:border-white/25 hover:text-white disabled:opacity-30';
                b.textContent = label;
                b.title = title;
                b.disabled = disabled;
                b.addEventListener('click', handler);
                return b;
            };

            actions.appendChild(mkBtn('↑', idx === 0, 'Subir pregunta', () => {
                [faqPairs[idx - 1], faqPairs[idx]] = [faqPairs[idx], faqPairs[idx - 1]];
                renderFaqBuilder();
                syncFaqTextareaFromBuilder();
                markDirty(true);
            }));

            actions.appendChild(mkBtn('↓', idx === faqPairs.length - 1, 'Bajar pregunta', () => {
                [faqPairs[idx + 1], faqPairs[idx]] = [faqPairs[idx], faqPairs[idx + 1]];
                renderFaqBuilder();
                syncFaqTextareaFromBuilder();
                markDirty(true);
            }));

            actions.appendChild(mkBtn('Quitar', false, 'Eliminar pregunta', () => {
                faqPairs.splice(idx, 1);
                renderFaqBuilder();
                syncFaqTextareaFromBuilder();
                markDirty(true);
            }));

            top.appendChild(actions);
            card.appendChild(top);

            const qInput = document.createElement('input');
            qInput.type = 'text';
            qInput.className = 'field text-xs';
            qInput.placeholder = 'Ej. ¿Hacen entregas en el barrio?';
            qInput.value = pair.q;
            qInput.addEventListener('input', () => {
                faqPairs[idx].q = qInput.value;
                syncFaqTextareaFromBuilder();
                markDirty(true);
            });
            card.appendChild(qInput);

            const aInput = document.createElement('input');
            aInput.type = 'text';
            aInput.className = 'field text-xs';
            aInput.placeholder = 'Respuesta clara y breve…';
            aInput.value = pair.a;
            aInput.addEventListener('input', () => {
                faqPairs[idx].a = aInput.value;
                syncFaqTextareaFromBuilder();
                markDirty(true);
            });
            card.appendChild(aInput);

            faqBuilder.appendChild(card);
        });
    };

    if (faqBuilder) {
        renderFaqBuilder();
    }

    faqAddBtn?.addEventListener('click', () => {
        faqPairs.push({ q: '¿Nueva consulta?', a: '' });
        renderFaqBuilder();
        syncFaqTextareaFromBuilder();
        markDirty(true);
        const inputs = faqBuilder?.querySelectorAll('input');
        inputs?.[inputs.length - 2]?.focus();
    });

    faqToggleRawBtn?.addEventListener('click', () => {
        if (!faqRawWrap || !faqBuilder) return;
        const showRaw = faqRawWrap.classList.contains('hidden');
        if (!showRaw && faqTextarea) {
            faqPairs = parseFaqLines(faqTextarea.value);
            renderFaqBuilder();
        }
        faqRawWrap.classList.toggle('hidden', !showRaw);
        faqBuilder.classList.toggle('hidden', showRaw);
        faqToggleRawBtn.textContent = showRaw ? 'Modo visual' : 'Modo texto';
    });

    faqTextarea?.addEventListener('input', () => {
        faqPairs = parseFaqLines(faqTextarea.value);
        markDirty(true);
        syncPreview();
    });

    // Section tabs filtering
    tabButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.sectionTab;
            tabButtons.forEach((b) => {
                const active = b === btn;
                b.setAttribute('aria-selected', active ? 'true' : 'false');
                b.classList.toggle('border-fuchsia-400/40', active);
                b.classList.toggle('bg-fuchsia-500/20', active);
                b.classList.toggle('text-fuchsia-100', active);
                b.classList.toggle('border-white/10', !active);
                b.classList.toggle('bg-white/[.03]', !active);
                b.classList.toggle('text-zinc-400', !active);
            });

            sectionCards.forEach((card) => {
                const match = target === 'all' || card.dataset.sectionCard === target;
                card.classList.toggle('hidden', !match);
            });
        });
    });

    // Quick CTA destination presets
    siteEditor.querySelectorAll('[data-cta-preset]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.ctaTarget;
            const input = targetId ? document.getElementById(targetId) : null;
            if (!input) return;
            input.value = btn.dataset.ctaPreset || '#productos';
            markDirty(true);
            syncPreview();
        });
    });

    // Reset section to suggested defaults
    siteEditor.querySelectorAll('[data-reset-section]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const key = btn.dataset.resetSection;
            const d = defaults[key];
            if (!d) return;

            const titleEl = getField(key, 'title');
            const contentEl = getField(key, 'content');
            const ctaLabelEl = getField(key, 'cta_label');
            const ctaUrlEl = getField(key, 'cta_url');
            const activeEl = getField(key, 'active');

            if (titleEl) titleEl.value = d.title ?? '';
            if (contentEl) contentEl.value = d.content ?? '';
            if (ctaLabelEl) ctaLabelEl.value = d.cta_label ?? '';
            if (ctaUrlEl) ctaUrlEl.value = d.cta_url ?? '';
            if (activeEl) activeEl.checked = true;

            if (key === 'faq' && contentEl) {
                faqPairs = parseFaqLines(contentEl.value);
                renderFaqBuilder();
            }

            markDirty(true);
            updateCharCounters();
            syncPreview();
        });
    });

    // Listen to all editor inputs for instant preview sync
    siteEditor.querySelectorAll('[data-section-input]').forEach((input) => {
        const evt = input.type === 'checkbox' ? 'change' : 'input';
        input.addEventListener(evt, () => {
            markDirty(true);
            updateCharCounters();
            syncPreview();
        });
    });

    // Preview Desktop vs Mobile switcher
    const modeButtons = siteEditor.querySelectorAll('[data-preview-mode]');
    modeButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const isMobile = btn.dataset.previewMode === 'mobile';
            modeButtons.forEach((b) => {
                const active = b === btn;
                b.setAttribute('aria-pressed', active ? 'true' : 'false');
                b.classList.toggle('bg-fuchsia-500/25', active);
                b.classList.toggle('text-fuchsia-100', active);
                b.classList.toggle('text-zinc-400', !active);
            });
            previewContainer?.classList.toggle('max-w-[375px]', isMobile);
            previewContainer?.classList.toggle('max-w-full', !isMobile);
        });
    });

    // Preview jump buttons
    siteEditor.querySelectorAll('[data-preview-scroll]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = previewContainer?.querySelector(`[data-pv-block="${btn.dataset.previewScroll}"]`);
            const scrollParent = previewContainer?.parentElement;
            if (target && scrollParent) {
                const offset = target.offsetTop - 12;
                scrollParent.scrollTo({ top: Math.max(0, offset), behavior: prefersReducedMotion ? 'auto' : 'smooth' });
            }
        });
    });

    form?.addEventListener('submit', () => {
        isSubmitting = true;
    });

    window.addEventListener('beforeunload', (e) => {
        if (isDirty && !isSubmitting) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    updateCharCounters();
    syncPreview();
}
