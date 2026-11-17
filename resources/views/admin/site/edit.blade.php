@extends('layouts.admin')
@section('title', 'Editar página web')
@section('heading', 'Editor de página web')
@section('content')
    <div data-cinema-enter class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-fuchsia-300/90">Presencia digital</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-white">Tu página, a tu manera</h1>
            <p class="mt-1 text-xs text-zinc-400">Actualiza publicidad multimedia (imágenes o videos en base de datos o URL), textos, botones y WhatsApp de tu vitrina pública.</p>
        </div>
        <a target="_blank" href="{{ route('home') }}" class="btn-subtle text-xs">
            <x-icon name="store" class="size-4 text-amber-300" />
            <span>Abrir sitio público</span>
            <x-icon name="arrow-up-right" class="size-3.5" />
        </a>
    </div>

    @if($errors->any())
        <div role="alert" class="mt-5 rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-xs font-semibold text-rose-200">
            <div class="flex items-center gap-2">
                <x-icon name="alert-triangle" class="size-4 text-rose-400" />
                <span>Por favor verifica los datos ingresados: {{ $errors->first() }}</span>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.site.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf @method('PUT')

        @php
            $promoSection = $sections['promo'] ?? null;
        @endphp

        {{-- =======================================================================
             1. APARTADO DE PUBLICIDAD Y PRESENTACIÓN MULTIMEDIA (ZONA SUPERIOR VERDE)
             ======================================================================= --}}
        @if($promoSection)
            <section data-reveal class="panel spotlight-card rounded-3xl border border-emerald-400/20 p-6 sm:p-7">
                <input type="hidden" name="sections[promo][title]" value="{{ old('sections.promo.title', $promoSection->title ?: 'Promociones y presentación de temporada') }}">
                <input type="hidden" name="sections[promo][content]" value="{{ old('sections.promo.content', $promoSection->content) }}">

                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-white/[.08] pb-5">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 text-[10px] font-extrabold uppercase tracking-[.2em] text-emerald-300">
                                <x-icon name="sparkles" class="size-3.5" />
                                Espacio Superior Verde · Publicidad y Presentación
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-fuchsia-400/25 bg-fuchsia-400/10 px-3 py-1 text-[10px] font-bold text-fuchsia-200">
                                <x-icon name="database" class="size-3.5 text-fuchsia-300" />
                                Guardado en Base de Datos (Portátil entre equipos)
                            </span>
                        </div>
                        <h2 class="mt-2.5 text-xl font-extrabold text-white">Publicidad, Banner o Presentación Multimedia (Imagen / Video)</h2>
                        <p class="mt-1 max-w-3xl text-xs leading-relaxed text-zinc-400">
                            Personaliza el apartado superior verde de la portada pública. Puedes subir archivos locales desde tu computadora (se almacenan directamente en la base de datos para que carguen en cualquier otra laptop sin perderse) o pegar una URL web de imagen, video MP4 o YouTube/Vimeo.
                        </p>
                    </div>

                    <label class="inline-flex cursor-pointer items-center gap-2.5 rounded-full border border-emerald-400/25 bg-emerald-400/10 px-4 py-2 text-xs font-bold text-emerald-200 transition hover:border-emerald-400/45">
                        <input type="hidden" name="sections[promo][active]" value="0">
                        <input class="size-4 rounded accent-emerald-400" type="checkbox" name="sections[promo][active]" value="1" @checked(old('sections.promo.active', $promoSection->active))>
                        Visible en el sitio
                    </label>
                </div>

                {{-- Lista de Anuncios / Diapositivas Actuales --}}
                <div class="mt-6 space-y-6">
                    @foreach($promoMedia as $item)
                        @php
                            $itemSource = old("media_items.{$item->id}.source_type", $item->source_type ?: 'upload');
                            $previewUrl = $item->mediaUrl();
                            $embedUrl = $item->embedUrl();
                            $hasDbData = $item->hasDatabaseBinary();
                        @endphp
                        <div data-media-editor-card class="rounded-2xl border border-white/10 bg-white/[.025] p-5 transition hover:border-white/20">
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/[.06] pb-3.5">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <span class="font-mono-num inline-flex items-center rounded-lg bg-fuchsia-500/20 px-2.5 py-1 text-xs font-extrabold text-fuchsia-300">
                                        #{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                    <h3 class="text-sm font-extrabold text-white">
                                        {{ $item->title ?: 'Anuncio / Presentación Principal' }}
                                    </h3>
                                    @if($hasDbData)
                                        <span class="inline-flex items-center gap-1 rounded-full border border-emerald-400/30 bg-emerald-400/10 px-2.5 py-0.5 text-[10px] font-bold text-emerald-300">
                                            <x-icon name="database" class="size-3" />
                                            Almacenado en BD {{ $item->formattedSize() ? '· '.$item->formattedSize() : '' }}
                                        </span>
                                    @endif
                                    @if($item->source_type === 'url')
                                        <span class="inline-flex items-center gap-1 rounded-full border border-cyan-400/30 bg-cyan-400/10 px-2.5 py-0.5 text-[10px] font-bold text-cyan-300">
                                            <x-icon name="link" class="size-3" />
                                            URL Web
                                        </span>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-white/10 bg-white/[.04] px-3 py-1.5 text-[11px] font-semibold text-zinc-300">
                                        <input type="hidden" name="media_items[{{ $item->id }}][active]" value="0">
                                        <input type="checkbox" class="size-3.5 rounded accent-fuchsia-500" name="media_items[{{ $item->id }}][active]" value="1" @checked(old("media_items.{$item->id}.active", $item->active))>
                                        Activo
                                    </label>

                                    @if($promoMedia->count() > 1)
                                        <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-rose-500/25 bg-rose-500/10 px-3 py-1.5 text-[11px] font-semibold text-rose-300 transition hover:bg-rose-500/20">
                                            <input type="checkbox" class="size-3.5 rounded accent-rose-500" name="media_items[{{ $item->id }}][delete]" value="1">
                                            <x-icon name="trash" class="size-3.5" />
                                            Eliminar al guardar
                                        </label>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-5 grid gap-6 lg:grid-cols-[.95fr_1.05fr]">
                                {{-- Columna Izquierda: Vista Previa en Vivo + Selección de Origen (Local / URL) --}}
                                <div class="space-y-4">
                                    <div class="overflow-hidden rounded-2xl border border-white/15 bg-black/50">
                                        <div class="flex items-center justify-between border-b border-white/10 bg-white/[.03] px-3.5 py-2 text-[11px] font-bold text-zinc-400">
                                            <span class="flex items-center gap-1.5">
                                                <x-icon :name="$item->media_type === 'video' ? 'video' : 'image'" class="size-3.5 text-fuchsia-300" />
                                                Vista previa del espacio verde
                                            </span>
                                            <span data-preview-status class="text-[10px] font-semibold text-emerald-300">
                                                {{ $item->file_name ?: ($item->source_type === 'url' ? 'Enlace externo' : 'Listo') }}
                                            </span>
                                        </div>

                                        <div class="relative flex min-h-[210px] items-center justify-center bg-[#121C0E]">
                                            <img data-preview-img
                                                 src="{{ $item->media_type === 'image' ? $previewUrl : '' }}"
                                                 alt="Vista previa"
                                                 class="{{ $item->media_type === 'image' && $previewUrl ? 'block' : 'hidden' }} max-h-[250px] w-full object-contain">

                                            <video data-preview-video controls playsinline
                                                   src="{{ ($item->media_type === 'video' && ! $embedUrl) ? $previewUrl : '' }}"
                                                   class="{{ ($item->media_type === 'video' && ! $embedUrl && $previewUrl) ? 'block' : 'hidden' }} max-h-[250px] w-full object-contain"></video>

                                            <iframe data-preview-iframe
                                                    src="{{ ($item->media_type === 'video' && $embedUrl) ? $embedUrl : '' }}"
                                                    title="Vista previa de video"
                                                    class="{{ ($item->media_type === 'video' && $embedUrl) ? 'block' : 'hidden' }} h-[230px] w-full border-0"
                                                    allowfullscreen></iframe>
                                        </div>
                                    </div>

                                    {{-- Selector de Modo: Subida Local vs URL Web --}}
                                    <div>
                                        <input type="hidden" data-source-input name="media_items[{{ $item->id }}][source_type]" value="{{ $itemSource }}">
                                        <div class="grid grid-cols-2 gap-2 rounded-xl border border-white/10 bg-black/30 p-1">
                                            <button type="button" data-source-tab="upload"
                                                    class="flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-bold transition {{ $itemSource === 'upload' ? 'bg-fuchsia-500/25 text-white ring-1 ring-fuchsia-400/40' : 'text-zinc-400 hover:text-white' }}">
                                                <x-icon name="upload" class="size-3.5" />
                                                <span>Subir archivo local</span>
                                            </button>
                                            <button type="button" data-source-tab="url"
                                                    class="flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-bold transition {{ $itemSource === 'url' ? 'bg-fuchsia-500/25 text-white ring-1 ring-fuchsia-400/40' : 'text-zinc-400 hover:text-white' }}">
                                                <x-icon name="link" class="size-3.5" />
                                                <span>Usar URL web</span>
                                            </button>
                                        </div>

                                        {{-- Panel Subida Local --}}
                                        <div data-source-panel="upload" class="{{ $itemSource === 'upload' ? 'block' : 'hidden' }} mt-3">
                                            <label class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-fuchsia-400/30 bg-fuchsia-500/[.05] px-4 py-5 text-center transition hover:border-fuchsia-400/60 hover:bg-fuchsia-500/[.09]">
                                                <x-icon name="upload" class="size-6 text-fuchsia-300" />
                                                <span class="mt-2 text-xs font-extrabold text-white">Haz clic para elegir otra imagen o video de tu computadora</span>
                                                <span class="mt-1 text-[11px] text-zinc-400">Soporta JPG, PNG, WEBP, GIF, SVG, AVIF y videos MP4, WEBM, MOV (se guarda en la Base de Datos)</span>
                                                <input type="file"
                                                       data-media-file-input
                                                       name="media_items[{{ $item->id }}][file]"
                                                       accept="image/*,video/mp4,video/webm,video/ogg,video/quicktime"
                                                       class="mt-3 block w-full text-xs text-zinc-300 file:mr-3 file:rounded-full file:border-0 file:bg-fuchsia-500/20 file:px-3.5 file:py-1.5 file:text-xs file:font-bold file:text-fuchsia-200 hover:file:bg-fuchsia-500/30">
                                            </label>
                                        </div>

                                        {{-- Panel URL Web --}}
                                        <div data-source-panel="url" class="{{ $itemSource === 'url' ? 'block' : 'hidden' }} mt-3">
                                            <label class="block text-xs font-bold text-zinc-300">
                                                Enlace URL de la imagen o video (web directa, YouTube o Vimeo)
                                                <input type="url"
                                                       data-media-url-input
                                                       name="media_items[{{ $item->id }}][external_url]"
                                                       value="{{ old("media_items.{$item->id}.external_url", $item->external_url) }}"
                                                       placeholder="https://ejemplo.com/publicidad.jpg o https://www.youtube.com/watch?v=..."
                                                       class="field mt-2 text-xs">
                                            </label>
                                            <p class="mt-1.5 text-[11px] text-zinc-400">
                                                Si pegas el enlace de una imagen web, el sistema también guarda una copia de respaldo en la base de datos automáticamente.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Columna Derecha: Configuración Visual, Textos y Botón del Anuncio --}}
                                <div class="grid content-start gap-3.5 sm:grid-cols-2">
                                    <label class="text-xs font-bold text-zinc-300">
                                        Tipo de archivo
                                        <select name="media_items[{{ $item->id }}][media_type]" data-media-type-select class="field mt-1.5 text-xs">
                                            <option value="auto">Automático (Detectar imagen o video)</option>
                                            <option value="image" @selected(old("media_items.{$item->id}.media_type", $item->media_type) === 'image')>Imagen (Banner / Afiche / Foto)</option>
                                            <option value="video" @selected(old("media_items.{$item->id}.media_type", $item->media_type) === 'video')>Video (MP4 / WebM / YouTube)</option>
                                        </select>
                                    </label>

                                    <label class="text-xs font-bold text-zinc-300">
                                        Ajuste en el espacio verde
                                        <select name="media_items[{{ $item->id }}][fit_mode]" class="field mt-1.5 text-xs">
                                            <option value="cover" @selected(old("media_items.{$item->id}.fit_mode", $item->fit_mode) === 'cover')>Panorámico (Llenar todo el espacio)</option>
                                            <option value="contain" @selected(old("media_items.{$item->id}.fit_mode", $item->fit_mode) === 'contain')>Completo sin recortar (Flyer / Póster)</option>
                                            <option value="auto" @selected(old("media_items.{$item->id}.fit_mode", $item->fit_mode) === 'auto')>Adaptable a su proporción original</option>
                                        </select>
                                    </label>

                                    <div class="sm:col-span-2">
                                        <label class="inline-flex cursor-pointer items-center gap-2.5 rounded-xl border border-white/10 bg-white/[.03] px-3.5 py-2 text-xs font-semibold text-zinc-200">
                                            <input type="hidden" name="media_items[{{ $item->id }}][show_overlay]" value="0">
                                            <input type="checkbox" class="size-4 rounded accent-fuchsia-500" name="media_items[{{ $item->id }}][show_overlay]" value="1" @checked(old("media_items.{$item->id}.show_overlay", $item->show_overlay))>
                                            <span>Mostrar textos y botón encima de la imagen/video (desmarca si tu imagen ya tiene diseño propio)</span>
                                        </label>
                                    </div>

                                    <label class="text-xs font-bold text-zinc-300">
                                        Etiqueta superior (opcional)
                                        <input class="field mt-1.5 text-xs" name="media_items[{{ $item->id }}][badge]" value="{{ old("media_items.{$item->id}.badge", $item->badge) }}" maxlength="80" placeholder="Ej. OFERTA DE LA SEMANA">
                                    </label>

                                    <label class="text-xs font-bold text-zinc-300">
                                        Título en presentación (opcional)
                                        <input class="field mt-1.5 text-xs" name="media_items[{{ $item->id }}][title]" value="{{ old("media_items.{$item->id}.title", $item->title) }}" maxlength="160" placeholder="Ej. Promociones frescas todos los días">
                                    </label>

                                    <label class="text-xs font-bold text-zinc-300 sm:col-span-2">
                                        Descripción o mensaje publicitario (opcional)
                                        <textarea class="field mt-1.5 min-h-20 text-xs" name="media_items[{{ $item->id }}][subtitle]" maxlength="1000" placeholder="Texto breve que acompaña tu imagen o video…">{{ old("media_items.{$item->id}.subtitle", $item->subtitle) }}</textarea>
                                    </label>

                                    <label class="text-xs font-bold text-zinc-300">
                                        Texto del botón (opcional)
                                        <input class="field mt-1.5 text-xs" name="media_items[{{ $item->id }}][cta_label]" value="{{ old("media_items.{$item->id}.cta_label", $item->cta_label) }}" maxlength="80" placeholder="Ej. Ver ofertas">
                                    </label>

                                    <label class="text-xs font-bold text-zinc-300">
                                        Enlace del botón (opcional)
                                        <input class="field mt-1.5 text-xs" name="media_items[{{ $item->id }}][cta_url]" value="{{ old("media_items.{$item->id}.cta_url", $item->cta_url) }}" maxlength="255" placeholder="#productos o https://wa.me/...">
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Botón y Panel para Agregar Nueva Diapositiva / Imagen / Video Adicional --}}
                <div class="mt-6 rounded-2xl border border-dashed border-fuchsia-400/30 bg-fuchsia-500/[.03] p-5" data-media-editor-card>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <span class="grid size-9 place-items-center rounded-xl bg-fuchsia-500/20 text-fuchsia-300">
                                <x-icon name="plus" class="size-4" />
                            </span>
                            <div>
                                <h3 class="text-sm font-extrabold text-white">Agregar nueva imagen o video (o reemplazar rápido)</h3>
                                <p class="text-[11px] text-zinc-400">Añade más diapositivas para crear un carrusel de presentación o reemplaza el anuncio principal.</p>
                            </div>
                        </div>
                        <button type="button" data-toggle-new-media
                                class="btn-subtle text-xs">
                            <x-icon name="plus" class="size-3.5 text-fuchsia-300" />
                            <span>Desplegar cargador multimedia</span>
                        </button>
                    </div>

                    <div id="new-media-drawer" class="{{ $promoMedia->isEmpty() ? 'block' : 'hidden' }} mt-5 border-t border-white/10 pt-5">
                        <div class="grid gap-6 lg:grid-cols-[.95fr_1.05fr]">
                            <div class="space-y-4">
                                <div class="overflow-hidden rounded-2xl border border-white/15 bg-black/50">
                                    <div class="flex items-center justify-between border-b border-white/10 bg-white/[.03] px-3.5 py-2 text-[11px] font-bold text-zinc-400">
                                        <span>Vista previa del nuevo archivo / enlace</span>
                                        <span data-preview-status class="text-[10px] font-semibold text-fuchsia-300">Esperando selección…</span>
                                    </div>
                                    <div class="relative flex min-h-[190px] items-center justify-center bg-[#121C0E] p-3">
                                        <p data-preview-placeholder class="text-center text-xs text-zinc-500">
                                            Selecciona una imagen/video local o pega una URL para previsualizar aquí antes de guardar.
                                        </p>
                                        <img data-preview-img src="" alt="Previsualización nueva" class="hidden max-h-[230px] w-full object-contain">
                                        <video data-preview-video controls playsinline class="hidden max-h-[230px] w-full object-contain"></video>
                                        <iframe data-preview-iframe title="Previsualización de video" class="hidden h-[220px] w-full border-0" allowfullscreen></iframe>
                                    </div>
                                </div>

                                <input type="hidden" data-source-input name="new_media[source_type]" value="upload">
                                <div class="grid grid-cols-2 gap-2 rounded-xl border border-white/10 bg-black/30 p-1">
                                    <button type="button" data-source-tab="upload"
                                            class="flex items-center justify-center gap-2 rounded-lg bg-fuchsia-500/25 px-3 py-2 text-xs font-bold text-white ring-1 ring-fuchsia-400/40 transition">
                                        <x-icon name="upload" class="size-3.5" />
                                        <span>Subida local (BD)</span>
                                    </button>
                                    <button type="button" data-source-tab="url"
                                            class="flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-bold text-zinc-400 transition hover:text-white">
                                        <x-icon name="link" class="size-3.5" />
                                        <span>Por URL web</span>
                                    </button>
                                </div>

                                <div data-source-panel="upload" class="block">
                                    <label class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-emerald-400/35 bg-emerald-500/[.05] px-4 py-4 text-center transition hover:border-emerald-400/60">
                                        <x-icon name="upload" class="size-5 text-emerald-300" />
                                        <span class="mt-1.5 text-xs font-extrabold text-white">Seleccionar imagen o video desde tu equipo</span>
                                        <input type="file"
                                               data-media-file-input
                                               name="new_media[file]"
                                               accept="image/*,video/mp4,video/webm,video/ogg,video/quicktime"
                                               class="mt-2.5 block w-full text-xs text-zinc-300 file:mr-3 file:rounded-full file:border-0 file:bg-emerald-500/20 file:px-3.5 file:py-1.5 file:text-xs file:font-bold file:text-emerald-200">
                                    </label>
                                </div>

                                <div data-source-panel="url" class="hidden">
                                    <label class="block text-xs font-bold text-zinc-300">
                                        URL de la imagen o video (enlace directo, YouTube o Vimeo)
                                        <input type="url"
                                               data-media-url-input
                                               name="new_media[external_url]"
                                               placeholder="https://..."
                                               class="field mt-1.5 text-xs">
                                    </label>
                                </div>
                            </div>

                            <div class="grid content-start gap-3.5 sm:grid-cols-2">
                                <label class="text-xs font-bold text-zinc-300">
                                    ¿Cómo deseas aplicarlo?
                                    <select name="new_media[action_mode]" class="field mt-1.5 text-xs">
                                        <option value="append">Agregar como nueva diapositiva (Presentación)</option>
                                        <option value="replace_first">Reemplazar el anuncio principal (#01)</option>
                                    </select>
                                </label>

                                <label class="text-xs font-bold text-zinc-300">
                                    Ajuste en el espacio verde
                                    <select name="new_media[fit_mode]" class="field mt-1.5 text-xs">
                                        <option value="cover">Panorámico (Llenar todo el espacio)</option>
                                        <option value="contain">Completo sin recortar (Flyer / Póster)</option>
                                        <option value="auto">Adaptable a su proporción original</option>
                                    </select>
                                </label>

                                <input type="hidden" name="new_media[media_type]" value="auto">

                                <div class="sm:col-span-2">
                                    <label class="inline-flex cursor-pointer items-center gap-2.5 rounded-xl border border-white/10 bg-white/[.03] px-3.5 py-2 text-xs font-semibold text-zinc-200">
                                        <input type="hidden" name="new_media[show_overlay]" value="0">
                                        <input type="checkbox" class="size-4 rounded accent-fuchsia-500" name="new_media[show_overlay]" value="1" checked>
                                        <span>Mostrar textos y botón sobre este anuncio</span>
                                    </label>
                                </div>

                                <label class="text-xs font-bold text-zinc-300">
                                    Etiqueta superior (opcional)
                                    <input class="field mt-1.5 text-xs" name="new_media[badge]" maxlength="80" placeholder="Ej. PROMOCIÓN ESPECIAL">
                                </label>

                                <label class="text-xs font-bold text-zinc-300">
                                    Título (opcional)
                                    <input class="field mt-1.5 text-xs" name="new_media[title]" maxlength="160" placeholder="Título para la presentación">
                                </label>

                                <label class="text-xs font-bold text-zinc-300 sm:col-span-2">
                                    Descripción breve (opcional)
                                    <textarea class="field mt-1.5 min-h-16 text-xs" name="new_media[subtitle]" maxlength="1000" placeholder="Descripción opcional…"></textarea>
                                </label>

                                <label class="text-xs font-bold text-zinc-300">
                                    Texto del botón (opcional)
                                    <input class="field mt-1.5 text-xs" name="new_media[cta_label]" maxlength="80" placeholder="Ej. Pedir por WhatsApp">
                                </label>

                                <label class="text-xs font-bold text-zinc-300">
                                    Enlace del botón (opcional)
                                    <input class="field mt-1.5 text-xs" name="new_media[cta_url]" maxlength="255" placeholder="#productos o https://...">
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-white/[.06] pt-4">
                    <p class="text-[11px] text-zinc-400">
                        <strong class="text-zinc-200">Consejo:</strong> Al guardar, todas las imágenes y videos subidos localmente se almacenan por bloques en la base de datos SQL del proyecto.
                    </p>
                    <button type="submit" class="btn-primary text-xs">
                        <x-icon name="check-circle" class="size-4" />
                        <span>Guardar publicidad y cambios</span>
                    </button>
                </div>
            </section>
        @endif

        {{-- =======================================================================
             2. SECCIONES EDITORIALES DEL SITIO PÚBLICO
             ======================================================================= --}}
        @foreach($sections as $key => $section)
            @continue($key === 'promo')
            <section data-reveal style="--stagger: {{ $loop->index % 3 }}" class="panel spotlight-card rounded-3xl p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/[.06] pb-4">
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-[.22em] text-fuchsia-300">
                            {{ ['home' => 'Portada principal', 'banner' => 'Vitrina destacada', 'about' => 'Sobre nosotros', 'contact' => 'Contacto', 'faq' => 'Preguntas frecuentes'][$key] ?? $key }}
                        </p>
                        <h2 class="mt-1 text-lg font-extrabold text-white">{{ $section->title }}</h2>
                    </div>
                    <label class="inline-flex cursor-pointer items-center gap-2.5 rounded-full border border-white/10 bg-white/[.03] px-3.5 py-2 text-xs font-semibold text-zinc-300 transition hover:border-white/20">
                        <input type="hidden" name="sections[{{ $key }}][active]" value="0">
                        <input class="size-4 rounded accent-fuchsia-500" type="checkbox" name="sections[{{ $key }}][active]" value="1" @checked(old("sections.$key.active", $section->active))>
                        Visible en el sitio
                    </label>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label class="text-xs font-bold text-zinc-300">
                        Título
                        <input class="field mt-2" name="sections[{{ $key }}][title]" value="{{ old("sections.$key.title", $section->title) }}" maxlength="160" required>
                    </label>
                    <label class="text-xs font-bold text-zinc-300">
                        Texto / contenido
                        <textarea class="field mt-2 min-h-24" name="sections[{{ $key }}][content]" maxlength="4000">{{ old("sections.$key.content", $section->content) }}</textarea>
                    </label>
                    @if(in_array($key, ['home', 'banner', 'contact']))
                        <label class="text-xs font-bold text-zinc-300">
                            Texto del botón
                            <input class="field mt-2" name="sections[{{ $key }}][cta_label]" value="{{ old("sections.$key.cta_label", $section->cta_label) }}" maxlength="80">
                        </label>
                        @if(in_array($key, ['home', 'banner']))
                            <label class="text-xs font-bold text-zinc-300">
                                Enlace del botón
                                <input class="field mt-2" name="sections[{{ $key }}][cta_url]" value="{{ old("sections.$key.cta_url", $section->cta_url) }}" maxlength="255" placeholder="https://… o #productos">
                            </label>
                        @endif
                    @endif
                    @if($key === 'contact')
                        <label class="text-xs font-bold text-zinc-300 sm:col-span-2">
                            WhatsApp del negocio (código de país + número)
                            <input class="field font-mono-num mt-2" name="sections[contact][whatsapp]" value="{{ old('sections.contact.whatsapp', $section->whatsapp) }}" maxlength="32" placeholder="51912345678">
                        </label>
                    @endif
                </div>
            </section>
        @endforeach

        <div class="sticky bottom-20 z-20 flex justify-end pb-2 lg:bottom-5">
            <button class="btn-primary shadow-2xl shadow-black/60">
                <x-icon name="check-circle" class="size-4" />
                <span>Guardar cambios del sitio</span>
            </button>
        </div>
    </form>
@endsection
