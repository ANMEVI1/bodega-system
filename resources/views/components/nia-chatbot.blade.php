@props(['lowStockCount' => 0])

@php
    $criticalCount = max(0, (int) $lowStockCount);
    $hasCriticalStock = $criticalCount > 0;
    $launcherTitle = $hasCriticalStock ? 'Hay productos con stock bajo' : 'Abrir Asistente NIA';
@endphp

<div class="nia-cb-root print:hidden">
    {{-- Botón Flotante (Launcher) --}}
    <button
        type="button"
        id="nia-cb-launcher"
        class="nia-cb-launcher"
        title="{{ $launcherTitle }}"
        data-default-title="{{ $launcherTitle }}"
        data-low-stock="{{ $criticalCount }}"
        aria-label="{{ $launcherTitle }}"
        aria-expanded="false"
        aria-controls="nia-cb-window"
    >
        @if($hasCriticalStock)
            <span
                class="nia-cb-stock-badge"
                title="Hay productos con stock bajo ({{ $criticalCount }})"
                aria-label="Hay {{ $criticalCount }} productos con stock bajo"
            >
                <span class="nia-cb-stock-badge__dot" aria-hidden="true"></span>
            </span>
        @else
            <span class="nia-cb-status-dot" aria-hidden="true"></span>
        @endif

        <span class="nia-cb-launcher__icon nia-cb-launcher__icon--open" aria-hidden="true">
            <x-icon name="sparkles" class="size-6" />
        </span>
        <span class="nia-cb-launcher__icon nia-cb-launcher__icon--close" aria-hidden="true">
            <x-icon name="x" class="size-6" />
        </span>
    </button>

    {{-- Ventana Flotante del Asistente NIA --}}
    <section
        id="nia-cb-window"
        class="nia-cb-window"
        role="dialog"
        aria-modal="false"
        aria-hidden="true"
        aria-label="Asistente NIA"
        data-endpoint="{{ route('admin.chat') }}"
        data-csrf="{{ csrf_token() }}"
    >
        {{-- Cabecera --}}
        <header class="nia-cb-header">
            <div class="nia-cb-header__info">
                <div class="nia-cb-avatar" aria-hidden="true">
                    <x-icon name="sparkles" class="size-5" />
                    <span class="nia-cb-avatar__online"></span>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 class="nia-cb-title">Asistente NIA</h3>
                        @if($hasCriticalStock)
                            <span class="nia-cb-header__alert" title="Hay productos con stock bajo">
                                {{ $criticalCount }} stock bajo
                            </span>
                        @endif
                    </div>
                    <p class="nia-cb-subtitle">
                        <span class="nia-cb-subtitle__dot" aria-hidden="true"></span>
                        <span>Motor local instantáneo</span>
                    </p>
                </div>
            </div>

            <div class="nia-cb-header__actions">
                <button
                    type="button"
                    id="nia-cb-clear"
                    class="nia-cb-icon-btn"
                    title="Limpiar chat"
                    aria-label="Limpiar chat"
                >
                    <x-icon name="trash" class="size-4" />
                </button>
                <button
                    type="button"
                    id="nia-cb-minimize"
                    class="nia-cb-icon-btn"
                    title="Minimizar asistente"
                    aria-label="Minimizar asistente"
                >
                    <x-icon name="chevron-down" class="size-4" />
                </button>
            </div>
        </header>

        {{-- Historial de Mensajes --}}
        <div id="nia-cb-body" class="nia-cb-body">
            <div id="nia-cb-messages" class="nia-cb-messages" aria-live="polite"></div>

            {{-- Indicador animado "Escribiendo..." --}}
            <div id="nia-cb-typing" class="nia-cb-typing" aria-live="polite" aria-hidden="true">
                <div class="nia-cb-msg__badge" aria-hidden="true">
                    <x-icon name="sparkles" class="size-3.5" />
                </div>
                <div class="nia-cb-typing__dots">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span class="sr-only">NIA está escribiendo…</span>
                </div>
            </div>
        </div>

        {{-- Chips de Comandos Rápidos --}}
        <div id="nia-cb-suggestions" class="nia-cb-suggestions">
            <p class="nia-cb-suggestions__label">Comandos rápidos</p>
            <div class="nia-cb-suggestions__list">
                <button
                    type="button"
                    class="nia-cb-chip"
                    data-nia-prompt="Resumen de ventas"
                    data-auto-submit="true"
                >
                    Resumen de ventas
                </button>
                <button
                    type="button"
                    class="nia-cb-chip font-mono-num"
                    data-nia-prompt="Registrar cliente: [Nombre completo] | Teléfono: [999999999] | Correo: [correo@ejemplo.com]"
                >
                    + Cliente rápido
                </button>
                <button
                    type="button"
                    class="nia-cb-chip font-mono-num"
                    data-nia-prompt="Registrar producto: [Nombre] | Precio: [0.00] | Costo: [0.00] | Stock: [0]"
                >
                    + Producto rápido
                </button>
            </div>
        </div>

        {{-- Pie / Formulario de Envío --}}
        <footer class="nia-cb-footer">
            <form
                id="nia-cb-form"
                class="nia-cb-form"
                action="{{ route('admin.chat') }}"
                method="POST"
                autocomplete="off"
            >
                @csrf
                <textarea
                    id="nia-cb-input"
                    class="nia-cb-input"
                    name="message"
                    rows="1"
                    maxlength="500"
                    placeholder="Escribe un comando o consulta para NIA…"
                    aria-label="Escribe un comando o consulta para NIA"
                    required
                ></textarea>
                <button
                    type="submit"
                    id="nia-cb-send"
                    class="nia-cb-send-btn"
                    aria-label="Enviar mensaje"
                    disabled
                >
                    <x-icon name="send" class="size-4" />
                </button>
            </form>
            <div class="nia-cb-footer__meta">
                <span>Enter para enviar · Esc para cerrar</span>
                <span id="nia-cb-counter" class="font-mono-num">0/500</span>
            </div>
        </footer>
    </section>
</div>
