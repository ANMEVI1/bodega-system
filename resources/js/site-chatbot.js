/**
 * Asistente Público de Tienda — Cliente final
 * Sincronizado dinámicamente con el contenido editorial y catálogo público de Laravel.
 */

const STORAGE_HISTORY = 'bodega_public_chat_history_v1';
const STORAGE_OPEN = 'bodega_public_chat_open_v1';
const MAX_MESSAGES = 40;
const MAX_CHARS = 400;

function nowTimeLabel() {
    return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function getPublicCsrf(chatWindow) {
    return (
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
        chatWindow.querySelector('input[name="_token"]')?.value ||
        chatWindow.dataset.csrf ||
        ''
    );
}

function scrollToSectionAndPrefill(targetSelector, prefillText) {
    const target = document.querySelector(targetSelector || '#contacto');
    if (!target) return;

    if (prefillText) {
        const messageField = target.querySelector('textarea[name="message"]');
        if (messageField) {
            messageField.value = prefillText;
            messageField.focus({ preventScroll: true });
        }
    }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const top = target.getBoundingClientRect().top + window.scrollY - 76;
    window.scrollTo({ top: Math.max(0, top), behavior: reducedMotion ? 'auto' : 'smooth' });
}

export function initSiteChatbot() {
    const launcher = document.getElementById('site-cb-launcher');
    const chatWindow = document.getElementById('site-cb-window');
    const closeBtn = document.getElementById('site-cb-close');
    const clearBtn = document.getElementById('site-cb-clear');
    const bodyBox = document.getElementById('site-cb-body');
    const messagesBox = document.getElementById('site-cb-messages');
    const typingBox = document.getElementById('site-cb-typing');
    const suggestionsBox = document.getElementById('site-cb-suggestions');
    const form = document.getElementById('site-cb-form');
    const input = document.getElementById('site-cb-input');
    const sendBtn = document.getElementById('site-cb-send');
    const counter = document.getElementById('site-cb-counter');

    if (!launcher || !chatWindow || !messagesBox || !form || !input) {
        return;
    }

    if (launcher.dataset.initialized === 'true') {
        return;
    }
    launcher.dataset.initialized = 'true';

    const endpoint = chatWindow.dataset.endpoint || form.getAttribute('action') || '/asistente-tienda';
    const starterChips = (() => {
        try {
            const parsed = JSON.parse(chatWindow.dataset.starterChips || '[]');
            return Array.isArray(parsed) ? parsed : [];
        } catch {
            return [];
        }
    })();

    let isOpen = false;
    let isLoading = false;
    let history = [];

    const buildWelcomeMessage = () => ({
        sender: 'bot',
        text: '¡Hola! Bienvenido. Pregúntame por productos disponibles, precios en tienda, dudas frecuentes o cómo hacer tu pedido.',
        time: nowTimeLabel(),
        products: [],
        actions: [],
    });

    try {
        isOpen = sessionStorage.getItem(STORAGE_OPEN) === 'true';
        const raw = sessionStorage.getItem(STORAGE_HISTORY);
        if (raw) {
            const parsed = JSON.parse(raw);
            if (Array.isArray(parsed)) {
                history = parsed.filter((m) => m && typeof m.text === 'string' && typeof m.sender === 'string');
            }
        }
    } catch {
        history = [];
    }

    if (history.length === 0) {
        history = [buildWelcomeMessage()];
    }

    const persist = () => {
        try {
            sessionStorage.setItem(STORAGE_OPEN, String(isOpen));
            sessionStorage.setItem(STORAGE_HISTORY, JSON.stringify(history.slice(-MAX_MESSAGES)));
        } catch {
            // Storage quota / privacy mode safe fallback
        }
    };

    const scrollToBottom = () => {
        if (!bodyBox) return;
        requestAnimationFrame(() => {
            bodyBox.scrollTop = bodyBox.scrollHeight;
        });
    };

    const updateInputState = () => {
        const len = input.value.length;
        if (counter) counter.textContent = `${len}/${MAX_CHARS}`;
        if (sendBtn) sendBtn.disabled = isLoading || input.value.trim().length === 0;
        input.style.height = 'auto';
        input.style.height = `${Math.min(input.scrollHeight, 92)}px`;
    };

    const renderSuggestions = (items) => {
        if (!suggestionsBox) return;
        const chips = Array.isArray(items) && items.length ? items : starterChips;
        suggestionsBox.replaceChildren();

        chips.slice(0, 3).forEach((text) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'site-cb-chip';
            btn.textContent = text;
            btn.addEventListener('click', () => {
                if (isLoading) return;
                sendQuery(text);
            });
            suggestionsBox.appendChild(btn);
        });
    };

    const createMessageNode = (msg) => {
        const wrap = document.createElement('article');
        wrap.className = `site-cb-msg ${msg.sender === 'user' ? 'site-cb-msg--user' : 'site-cb-msg--bot'}`;

        const bubble = document.createElement('div');
        bubble.className = 'site-cb-bubble';
        bubble.textContent = msg.text;
        wrap.appendChild(bubble);

        if (msg.sender === 'bot' && Array.isArray(msg.products) && msg.products.length > 0) {
            const productsWrap = document.createElement('div');
            productsWrap.className = 'site-cb-products';

            msg.products.forEach((prod) => {
                const card = document.createElement('div');
                card.className = 'site-cb-product-card';

                const left = document.createElement('div');
                left.className = 'site-cb-product-card__meta';

                const cat = document.createElement('p');
                cat.className = 'site-cb-product-card__cat';
                cat.textContent = `${prod.category || 'Despensa'} · por ${prod.unit || 'unidad'}`;
                left.appendChild(cat);

                const name = document.createElement('p');
                name.className = 'site-cb-product-card__name';
                name.textContent = prod.name || '';
                left.appendChild(name);

                const status = document.createElement('p');
                status.className = `site-cb-product-card__status ${prod.low_stock ? 'site-cb-product-card__status--low' : 'site-cb-product-card__status--ok'}`;
                status.textContent = prod.status_label || 'En stock';
                left.appendChild(status);

                const right = document.createElement('div');
                right.className = 'site-cb-product-card__right';

                const price = document.createElement('span');
                price.className = 'site-cb-product-card__price';
                price.textContent = prod.price_formatted || '';
                right.appendChild(price);

                const askBtn = document.createElement('button');
                askBtn.type = 'button';
                askBtn.className = 'site-cb-product-card__btn';
                askBtn.textContent = 'Pedir';
                askBtn.addEventListener('click', () => {
                    if (window.innerWidth <= 768) {
                        setOpen(false);
                    }
                    scrollToSectionAndPrefill(
                        '#contacto',
                        `Hola, quisiera consultar disponibilidad y precio por "${prod.name}".`
                    );
                });
                right.appendChild(askBtn);

                card.appendChild(left);
                card.appendChild(right);
                productsWrap.appendChild(card);
            });

            wrap.appendChild(productsWrap);
        }

        if (msg.sender === 'bot' && Array.isArray(msg.actions) && msg.actions.length > 0) {
            const actionsWrap = document.createElement('div');
            actionsWrap.className = 'site-cb-actions';

            msg.actions.forEach((act) => {
                if (act.type === 'whatsapp' && act.url) {
                    const link = document.createElement('a');
                    link.className = 'site-cb-action-link';
                    link.href = act.url;
                    link.target = '_blank';
                    link.rel = 'noopener noreferrer';
                    link.textContent = `${act.label || 'Abrir WhatsApp'} ↗`;
                    actionsWrap.appendChild(link);
                } else {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'site-cb-action-link';
                    btn.textContent = `${act.label || 'Ver más'} →`;
                    btn.addEventListener('click', () => {
                        if (window.innerWidth <= 768) {
                            setOpen(false);
                        }
                        scrollToSectionAndPrefill(act.target || '#contacto', act.prefill || '');
                    });
                    actionsWrap.appendChild(btn);
                }
            });

            wrap.appendChild(actionsWrap);
        }

        const time = document.createElement('span');
        time.className = 'site-cb-msg__time';
        time.textContent = msg.time || '';
        wrap.appendChild(time);

        return wrap;
    };

    const renderHistory = () => {
        messagesBox.replaceChildren();
        history.forEach((msg) => messagesBox.appendChild(createMessageNode(msg)));
        scrollToBottom();
    };

    const setOpen = (next) => {
        isOpen = next;
        chatWindow.classList.toggle('is-open', isOpen);
        chatWindow.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        launcher.classList.toggle('is-open', isOpen);
        launcher.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        persist();
        if (isOpen) {
            scrollToBottom();
            setTimeout(() => input.focus({ preventScroll: true }), 80);
        }
    };

    const sendQuery = async (text) => {
        const message = String(text || '').trim();
        if (!message || isLoading) return;

        history.push({
            sender: 'user',
            text: message,
            time: nowTimeLabel(),
        });
        renderHistory();
        persist();

        input.value = '';
        isLoading = true;
        updateInputState();
        typingBox?.classList.add('is-visible');
        typingBox?.setAttribute('aria-hidden', 'false');
        scrollToBottom();

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getPublicCsrf(chatWindow),
                },
                body: JSON.stringify({ message }),
            });

            let payload = null;
            try {
                payload = await response.json();
            } catch {
                payload = null;
            }

            if (!response.ok) {
                const validationMsg = payload?.errors ? Object.values(payload.errors).flat()[0] : '';
                const errorText =
                    response.status === 419
                        ? 'Tu sesión web expiró. Recarga la página para seguir consultando.'
                        : response.status === 429
                            ? 'Demasiadas consultas seguidas. Espera unos segundos e inténtalo nuevamente.'
                            : validationMsg || payload?.reply || 'No pudimos responder tu consulta en este momento.';

                history.push({
                    sender: 'bot',
                    text: errorText,
                    time: nowTimeLabel(),
                    products: [],
                    actions: [],
                });
            } else {
                history.push({
                    sender: 'bot',
                    text: payload?.reply || '¿En qué más te puedo ayudar?',
                    time: nowTimeLabel(),
                    products: Array.isArray(payload?.products) ? payload.products : [],
                    actions: Array.isArray(payload?.actions) ? payload.actions : [],
                });

                if (Array.isArray(payload?.suggestions) && payload.suggestions.length) {
                    renderSuggestions(payload.suggestions);
                }
            }
        } catch {
            history.push({
                sender: 'bot',
                text: 'Ocurrió un problema de conexión. Inténtalo nuevamente o usa el formulario de contacto.',
                time: nowTimeLabel(),
                products: [],
                actions: [],
            });
        } finally {
            isLoading = false;
            typingBox?.classList.remove('is-visible');
            typingBox?.setAttribute('aria-hidden', 'true');
            updateInputState();
            renderHistory();
            persist();
        }
    };

    launcher.addEventListener('click', () => setOpen(!isOpen));
    closeBtn?.addEventListener('click', () => setOpen(false));
    clearBtn?.addEventListener('click', () => {
        history = [buildWelcomeMessage()];
        renderSuggestions(starterChips);
        renderHistory();
        persist();
    });

    suggestionsBox?.querySelectorAll('[data-site-prompt]').forEach((btn) => {
        btn.addEventListener('click', () => sendQuery(btn.dataset.sitePrompt || btn.textContent));
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        sendQuery(input.value);
    });

    input.addEventListener('input', updateInputState);
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendQuery(input.value);
        }
    });

    document.addEventListener('pointerdown', (e) => {
        if (!isOpen) return;
        const target = e.target;
        if (!(target instanceof Node)) return;
        if (!chatWindow.contains(target) && !launcher.contains(target)) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isOpen) {
            setOpen(false);
        }
    });

    renderHistory();
    updateInputState();
    setOpen(isOpen);
}
