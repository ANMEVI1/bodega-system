/**
 * Asistente NIA — Widget Flotante Global para el Panel Administrativo
 * Vanilla JavaScript ES Module (sin dependencias externas).
 */

const STORAGE_KEY_HISTORY = 'nia_admin_chat_history';
const STORAGE_KEY_OPEN = 'nia_admin_chat_open';
const LEGACY_KEYS = ['nexo_nia_chat_open_v1', 'nexo_nia_chat_messages_v1'];
const MAX_STORED_MESSAGES = 50;
const MAX_MESSAGE_LENGTH = 500;
const MIN_TYPING_FEEDBACK_MS = 260;

const BOT_SPARKLES_SVG = `
    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
    </svg>
`;

const WELCOME_TEXT =
    '¡Hola! Soy NIA, tu asistente operativo de NEXO Bodega.\n\n' +
    'Pregúntame por tus ventas del mes o registra clientes y productos al instante usando los comandos rápidos.';

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getCurrentTime() {
    return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function getCsrfToken(chatWindow) {
    const metaCsrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (metaCsrf) return metaCsrf;
    const formToken = document.querySelector('#nia-cb-form input[name="_token"]')?.value;
    return formToken || chatWindow.dataset.csrf || '';
}

function extractErrorMessage(status, payload) {
    const firstValidationError = payload?.errors
        ? Object.values(payload.errors).flat()[0]
        : '';

    if (payload?.reply) return payload.reply;
    if (firstValidationError) return firstValidationError;
    if (status === 419) return 'Tu sesión expiró. Recarga la página para continuar.';
    if (status === 429) return 'Demasiadas consultas seguidas. Espera unos segundos e inténtalo de nuevo.';
    if (payload?.message && status < 500) return payload.message;
    return 'No se pudo completar la solicitud. Inténtalo nuevamente.';
}

function shouldShowRefreshAction(actionCompleted) {
    if (!actionCompleted) return false;
    const path = window.location.pathname || '';

    if (actionCompleted === 'product_created') {
        return /\/admin\/(productos|products)(\/|$)/i.test(path);
    }

    if (actionCompleted === 'client_created') {
        return /\/admin\/(clientes|clients)(\/|$)/i.test(path);
    }

    return false;
}

function buildMetricsGridHtml(metrics) {
    if (!Array.isArray(metrics) || metrics.length === 0) {
        return '';
    }

    const cardsHtml = metrics
        .map((item) => {
            const label = escapeHtml(item?.label || '');
            const value = escapeHtml(item?.value || '');
            const subvalue = item?.subvalue ? `<span class="nia-cb-metric-card__sub">${escapeHtml(item.subvalue)}</span>` : '';
            const fullClass = item?.full_width ? ' nia-cb-metric-card--full' : '';
            const tone = ['purple', 'emerald', 'violet'].includes(item?.tone) ? item.tone : 'purple';

            return `
                <div class="nia-cb-metric-card${fullClass}">
                    <span class="nia-cb-metric-card__label">${label}</span>
                    <span class="nia-cb-metric-card__value nia-cb-metric-card__value--${tone}">${value}</span>
                    ${subvalue}
                </div>
            `;
        })
        .join('');

    return `<div class="nia-cb-metrics-grid">${cardsHtml}</div>`;
}

function selectFirstPlaceholder(textarea) {
    const val = textarea.value || '';
    const match = val.match(/\[[^\]]+\]/);
    textarea.focus();

    if (match && typeof match.index === 'number') {
        textarea.setSelectionRange(match.index, match.index + match[0].length);
    } else {
        textarea.setSelectionRange(0, val.length);
    }
}

export function initNiaChatbot() {
    const launcherBtn = document.getElementById('nia-cb-launcher');
    const chatWindow = document.getElementById('nia-cb-window');
    const minimizeBtn = document.getElementById('nia-cb-minimize');
    const clearBtn = document.getElementById('nia-cb-clear');
    const bodyContainer = document.getElementById('nia-cb-body');
    const messagesList = document.getElementById('nia-cb-messages');
    const typingIndicator = document.getElementById('nia-cb-typing');
    const chatForm = document.getElementById('nia-cb-form');
    const chatInput = document.getElementById('nia-cb-input');
    const sendBtn = document.getElementById('nia-cb-send');
    const charCounter = document.getElementById('nia-cb-counter');

    if (!launcherBtn || !chatWindow || !messagesList || !chatForm || !chatInput) {
        return;
    }

    if (launcherBtn.dataset.initialized === 'true') {
        return;
    }
    launcherBtn.dataset.initialized = 'true';

    const endpoint = chatWindow.dataset.endpoint || chatForm.getAttribute('action') || '/admin/asistente';
    const defaultLauncherTitle = launcherBtn.dataset.defaultTitle || launcherBtn.getAttribute('title') || 'Abrir Asistente NIA';

    let isOpen = false;
    let isLoading = false;
    let messages = [];

    try {
        LEGACY_KEYS.forEach((key) => sessionStorage.removeItem(key));
        isOpen = sessionStorage.getItem(STORAGE_KEY_OPEN) === 'true';
        const rawMessages = sessionStorage.getItem(STORAGE_KEY_HISTORY);
        if (rawMessages) {
            const parsed = JSON.parse(rawMessages);
            if (Array.isArray(parsed)) {
                messages = parsed.filter(
                    (msg) => msg && typeof msg.text === 'string' && typeof msg.sender === 'string'
                );
            }
        }
    } catch {
        isOpen = false;
        messages = [];
    }

    const persistState = () => {
        try {
            sessionStorage.setItem(STORAGE_KEY_OPEN, isOpen ? 'true' : 'false');
            sessionStorage.setItem(STORAGE_KEY_HISTORY, JSON.stringify(messages.slice(-MAX_STORED_MESSAGES)));
        } catch {
            // Ignora errores de almacenamiento en navegadores restringidos
        }
    };

    const scrollToBottom = () => {
        if (!bodyContainer) return;
        bodyContainer.scrollTop = bodyContainer.scrollHeight;
    };

    const updateInputState = () => {
        const value = chatInput.value || '';
        chatInput.style.height = 'auto';
        chatInput.style.height = `${Math.min(chatInput.scrollHeight, 92)}px`;

        if (charCounter) {
            charCounter.textContent = `${value.length}/${MAX_MESSAGE_LENGTH}`;
        }
        if (sendBtn) {
            sendBtn.disabled = isLoading || value.trim().length === 0;
        }
    };

    const renderMessages = () => {
        messagesList.innerHTML = '';

        messages.forEach((msg) => {
            const isUser = msg.sender === 'user';
            const row = document.createElement('div');
            row.className = `nia-cb-msg nia-cb-msg--${isUser ? 'user' : 'bot'}${msg.isError ? ' nia-cb-msg--error' : ''}`;

            const safeText = escapeHtml(msg.text);
            const safeTime = escapeHtml(msg.time || '');

            if (isUser) {
                row.innerHTML = `
                    <div class="nia-cb-msg__content">
                        <div class="nia-cb-bubble">${safeText}</div>
                        <div class="nia-cb-meta"><span>${safeTime}</span></div>
                    </div>
                `;
            } else {
                const metricsHtml = msg.type === 'metrics_card' ? buildMetricsGridHtml(msg.metrics) : '';
                const refreshHtml = shouldShowRefreshAction(msg.actionCompleted)
                    ? `<div class="nia-cb-refresh-wrap">
                           <button type="button" class="nia-cb-refresh-btn" data-nia-refresh="true">↻ Actualizar vista</button>
                       </div>`
                    : '';

                row.innerHTML = `
                    <div class="nia-cb-msg__badge" aria-hidden="true">${BOT_SPARKLES_SVG}</div>
                    <div class="nia-cb-msg__content">
                        <div class="nia-cb-bubble">${safeText}${metricsHtml}${refreshHtml}</div>
                        <div class="nia-cb-meta"><span>${safeTime}</span></div>
                    </div>
                `;
            }

            messagesList.appendChild(row);
        });

        scrollToBottom();
    };

    const setWelcomeMessage = () => {
        messages = [
            {
                sender: 'bot',
                text: WELCOME_TEXT,
                time: getCurrentTime(),
                isError: false,
            },
        ];
        persistState();
        renderMessages();
    };

    const setWindowOpen = (open, { focusInput = true } = {}) => {
        isOpen = Boolean(open);
        chatWindow.classList.toggle('is-open', isOpen);
        launcherBtn.classList.toggle('is-open', isOpen);
        launcherBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        launcherBtn.setAttribute('aria-label', isOpen ? 'Cerrar Asistente NIA' : defaultLauncherTitle);
        chatWindow.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        persistState();

        if (isOpen) {
            requestAnimationFrame(() => {
                scrollToBottom();
            });
            if (focusInput) {
                setTimeout(() => {
                    scrollToBottom();
                    chatInput.focus();
                }, 120);
            }
        }
    };

    const waitMinimumTyping = async (startTime) => {
        const remaining = MIN_TYPING_FEEDBACK_MS - (Date.now() - startTime);
        if (remaining > 0) {
            await new Promise((resolve) => setTimeout(resolve, remaining));
        }
    };

    const sendQuery = async (rawText) => {
        const query = String(rawText || '').trim();
        if (!query || isLoading) return;

        chatInput.value = '';
        isLoading = true;
        updateInputState();

        messages.push({
            sender: 'user',
            text: query,
            time: getCurrentTime(),
            isError: false,
        });

        persistState();
        renderMessages();

        if (typingIndicator) {
            typingIndicator.classList.add('is-visible');
            typingIndicator.setAttribute('aria-hidden', 'false');
            scrollToBottom();
        }

        const startTime = Date.now();

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(chatWindow),
                },
                credentials: 'same-origin',
                body: JSON.stringify({ message: query }),
            });

            const contentType = response.headers.get('content-type') || '';
            const payload = contentType.includes('application/json') ? await response.json() : null;

            await waitMinimumTyping(startTime);

            if (!response.ok) {
                messages.push({
                    sender: 'bot',
                    text: extractErrorMessage(response.status, payload),
                    time: getCurrentTime(),
                    isError: true,
                });
            } else {
                messages.push({
                    sender: 'bot',
                    text: payload?.reply || 'Consulta procesada correctamente.',
                    type: payload?.type || 'text',
                    metrics: Array.isArray(payload?.metrics) ? payload.metrics : null,
                    actionCompleted: payload?.action_completed || null,
                    time: getCurrentTime(),
                    isError: false,
                });
            }
        } catch {
            await waitMinimumTyping(startTime);
            messages.push({
                sender: 'bot',
                text: 'No se pudo conectar con el asistente. Verifica tu conexión e inténtalo nuevamente.',
                time: getCurrentTime(),
                isError: true,
            });
        } finally {
            isLoading = false;
            if (typingIndicator) {
                typingIndicator.classList.remove('is-visible');
                typingIndicator.setAttribute('aria-hidden', 'true');
            }
            persistState();
            renderMessages();
            updateInputState();
            if (isOpen) {
                chatInput.focus();
            }
        }
    };

    // Inicializar historial desde sessionStorage o mensaje de bienvenida
    if (!messages.length) {
        setWelcomeMessage();
    } else {
        renderMessages();
        requestAnimationFrame(scrollToBottom);
    }

    // Restaurar visibilidad inicial sin robar el foco al navegar
    setWindowOpen(isOpen, { focusInput: false });
    updateInputState();

    // Eventos del widget
    launcherBtn.addEventListener('click', () => {
        setWindowOpen(!isOpen);
    });

    minimizeBtn?.addEventListener('click', () => {
        setWindowOpen(false, { focusInput: false });
    });

    clearBtn?.addEventListener('click', () => {
        if (isLoading) return;
        try {
            sessionStorage.removeItem(STORAGE_KEY_HISTORY);
        } catch {
            // Ignora errores de sessionStorage
        }
        setWelcomeMessage();
        chatInput.focus();
    });

    // Limpieza de sessionStorage al cerrar sesión (logout)
    document.querySelectorAll('form[data-nia-logout], form[action$="/logout"]').forEach((logoutForm) => {
        logoutForm.addEventListener('submit', () => {
            try {
                sessionStorage.removeItem(STORAGE_KEY_HISTORY);
                sessionStorage.removeItem(STORAGE_KEY_OPEN);
            } catch {
                // Ignora errores de sessionStorage
            }
        });
    });

    // Delegación para el botón reactivo "↻ Actualizar vista"
    messagesList.addEventListener('click', (event) => {
        const refreshBtn = event.target.closest('[data-nia-refresh]');
        if (refreshBtn) {
            window.location.reload();
        }
    });

    // Chips de comandos rápidos con autocompletado y selección inteligente
    chatWindow.querySelectorAll('[data-nia-prompt]').forEach((chip) => {
        chip.addEventListener('click', () => {
            if (isLoading) return;
            const prompt = chip.dataset.niaPrompt || '';
            const autoSubmit = chip.dataset.autoSubmit === 'true';

            if (autoSubmit) {
                sendQuery(prompt);
                return;
            }

            chatInput.value = prompt;
            updateInputState();
            selectFirstPlaceholder(chatInput);
        });
    });

    // Selección automática de placeholders [campo] al hacer clic dentro del input
    chatInput.addEventListener('click', () => {
        if (chatInput.selectionStart !== chatInput.selectionEnd) return;
        const pos = chatInput.selectionStart ?? 0;
        const val = chatInput.value || '';
        const regex = /\[[^\]]+\]/g;
        let match;

        while ((match = regex.exec(val)) !== null) {
            const start = match.index;
            const end = start + match[0].length;
            if (pos >= start && pos <= end) {
                chatInput.setSelectionRange(start, end);
                break;
            }
        }
    });

    chatInput.addEventListener('input', updateInputState);

    chatInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            sendQuery(chatInput.value);
        }
    });

    chatForm.addEventListener('submit', (event) => {
        event.preventDefault();
        sendQuery(chatInput.value);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !isOpen) return;

        const confirmModal = document.getElementById('cinema-confirm-modal');
        if (confirmModal && !confirmModal.classList.contains('hidden')) {
            return;
        }

        setWindowOpen(false, { focusInput: false });
        launcherBtn.focus();
    });
}
