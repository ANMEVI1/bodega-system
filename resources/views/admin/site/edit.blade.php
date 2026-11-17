@extends('layouts.admin')
@section('title', 'Editar página web')
@section('heading', 'Editor de página web')
@section('content')
    <div data-cinema-enter class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-fuchsia-300/90">Presencia digital</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-white">Tu página, a tu manera</h1>
            <p class="mt-1 text-xs text-zinc-400">Actualiza textos, botones, visibilidad y WhatsApp de tu vitrina pública.</p>
        </div>
        <a target="_blank" href="{{ route('home') }}" class="btn-subtle text-xs">
            <x-icon name="store" class="size-4 text-amber-300" />
            <span>Abrir sitio público</span>
            <x-icon name="arrow-up-right" class="size-3.5" />
        </a>
    </div>

    <form method="POST" action="{{ route('admin.site.update') }}" class="mt-6 space-y-5">
        @csrf @method('PUT')
        @foreach($sections as $key => $section)
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
                                <input class="field mt-2" type="url" name="sections[{{ $key }}][cta_url]" value="{{ old("sections.$key.cta_url", $section->cta_url) }}" maxlength="255" placeholder="https://…">
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
