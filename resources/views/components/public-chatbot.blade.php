@props([
    'sections' => collect(),
    'products' => collect(),
    'categories' => collect(),
])

@php
    $faqActive = $sections->get('faq')?->active ?? false;
    $faqPairs = $faqActive ? \App\Models\SiteSection::parseFaqLines($sections->get('faq')?->content) : [];
    $featured = $products->first();
    $contactSection = $sections->get('contact');
    $hasContact = ($contactSection?->active ?? false) || filled($contactSection?->whatsapp);

    $starterChips = collect([
        $products->isNotEmpty() ? '¿Qué productos tienen disponibles?' : null,
        $featured ? 'Precio de '.$featured->name : null,
        $faqPairs[0]['q'] ?? null,
        $hasContact ? '¿Cómo hago un pedido?' : null,
    ])->filter()->unique()->take(3)->values();

    if ($starterChips->isEmpty()) {
        $starterChips = collect(['¿Qué venden en la bodega?', '¿Cómo hago un pedido?']);
    }

    $storeTitle = ($sections->get('home')?->active ? $sections->get('home')?->title : null)
        ?? ($sections->get('banner')?->active ? $sections->get('banner')?->title : null)
        ?? 'Bodega Los Olivos';
@endphp

<div class="site-cb-root print:hidden" data-site-chatbot>
    {{-- Botón flotante editorial --}}
    <button
        type="button"
        id="site-cb-launcher"
        class="site-cb-launcher"
        aria-label="Abrir asistente de tienda"
        aria-expanded="false"
        aria-controls="site-cb-window"
    >
        <span class="site-cb-launcher__dot" aria-hidden="true"></span>
        <span class="site-cb-launcher__icon site-cb-launcher__icon--open" aria-hidden="true">
            <x-icon name="message" class="size-4" />
        </span>
        <span class="site-cb-launcher__icon site-cb-launcher__icon--close" aria-hidden="true">
            <x-icon name="x" class="size-4" />
        </span>
        <span class="site-cb-launcher__label">Asistente</span>
    </button>

    {{-- Ventana flotante del asistente público --}}
    <section
        id="site-cb-window"
        class="site-cb-window"
        role="dialog"
        aria-modal="false"
        aria-hidden="true"
        aria-label="Asistente virtual de la tienda"
        data-endpoint="{{ route('site.chat') }}"
        data-csrf="{{ csrf_token() }}"
        data-store-title="{{ $storeTitle }}"
        data-starter-chips='{{ json_encode($starterChips->all(), JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG) }}'
    >
        <header class="site-cb-header">
            <div class="site-cb-header__brand">
                <span class="site-cb-avatar" aria-hidden="true">
                    <x-icon name="sprout" class="size-4" />
                </span>
                <div class="min-w-0">
                    <h3 class="site-cb-title font-editorial">Asistente de tienda</h3>
                    <p class="site-cb-subtitle">
                        <span class="site-cb-subtitle__dot" aria-hidden="true"></span>
                        <span>Catálogo, precios y atención al día</span>
                    </p>
                </div>
            </div>

            <div class="site-cb-header__actions">
                <button
                    type="button"
                    id="site-cb-clear"
                    class="site-cb-icon-btn"
                    title="Reiniciar conversación"
                    aria-label="Reiniciar conversación"
                >
                    <x-icon name="trash" class="size-3.5" />
                </button>
                <button
                    type="button"
                    id="site-cb-close"
                    class="site-cb-icon-btn"
                    title="Cerrar asistente"
                    aria-label="Cerrar asistente"
                >
                    <x-icon name="chevron-down" class="size-4" />
                </button>
            </div>
        </header>

        <div id="site-cb-body" class="site-cb-body">
            <div id="site-cb-messages" class="site-cb-messages" aria-live="polite"></div>

            <div id="site-cb-typing" class="site-cb-typing" aria-live="polite" aria-hidden="true">
                <span class="site-cb-typing__dot"></span>
                <span class="site-cb-typing__dot"></span>
                <span class="site-cb-typing__dot"></span>
                <span class="sr-only">Buscando respuesta en el catálogo…</span>
            </div>
        </div>

        <div id="site-cb-suggestions" class="site-cb-suggestions" aria-label="Consultas sugeridas">
            @foreach($starterChips as $chip)
                <button type="button" class="site-cb-chip" data-site-prompt="{{ $chip }}">{{ $chip }}</button>
            @endforeach
        </div>

        <footer class="site-cb-footer">
            <form
                id="site-cb-form"
                class="site-cb-form"
                action="{{ route('site.chat') }}"
                method="POST"
                autocomplete="off"
            >
                @csrf
                <textarea
                    id="site-cb-input"
                    class="site-cb-input"
                    name="message"
                    rows="1"
                    maxlength="400"
                    placeholder="Pregunta por productos, precios o atención…"
                    aria-label="Pregunta al asistente de tienda"
                    required
                ></textarea>
                <button
                    type="submit"
                    id="site-cb-send"
                    class="site-cb-send-btn"
                    aria-label="Enviar consulta"
                    disabled
                >
                    <x-icon name="send" class="size-4" />
                </button>
            </form>
            <div class="site-cb-footer__meta">
                <span>Enter para enviar · Esc para cerrar</span>
                <span id="site-cb-counter" class="font-mono-num">0/400</span>
            </div>
        </footer>
    </section>
</div>
