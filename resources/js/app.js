import './bootstrap';
import { initNiaChatbot } from './nia-chatbot';

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
                b.classList.toggle('bg-[#D7F0B4]', active);
                b.classList.toggle('text-[#202d15]', active);
                b.classList.toggle('border-[#253017]/25', active);
                b.classList.toggle('shadow-sm', active);
                b.classList.toggle('bg-white', !active);
                b.classList.toggle('text-[#4A463B]', !active);
                b.classList.toggle('border-[#253017]/15', !active);
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
            messageField.focus();
        }
        contactForm?.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth' });
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
 * 8. NIA Local Assistant Floating Widget
 * -------------------------------------------------------------------------- */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNiaChatbot);
} else {
    initNiaChatbot();
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
