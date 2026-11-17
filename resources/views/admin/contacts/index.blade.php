@extends('layouts.admin')
@section('title', 'Consultas web')
@section('heading', 'Consultas de clientes')
@section('content')
    <div data-cinema-enter>
        <p class="text-xs font-bold uppercase tracking-[.18em] text-fuchsia-300/90">Bandeja del sitio público</p>
        <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-white">
            Consultas <span class="font-mono-num text-fuchsia-300">({{ $contacts->total() }})</span>
        </h1>
        <p class="mt-1.5 text-xs text-zinc-400">Los mensajes enviados desde el formulario de tu página web llegan aquí al instante.</p>
    </div>

    <div class="mt-6 grid gap-5 xl:grid-cols-2">
        @forelse($contacts as $contact)
            @php
                $isNew = $contact->status === 'new';
            @endphp
            <article data-reveal style="--stagger: {{ $loop->index % 2 }}"
                     class="panel spotlight-card rounded-3xl p-6 {{ $isNew ? 'border-fuchsia-400/30' : '' }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h2 class="text-base font-extrabold text-white">{{ $contact->name }}</h2>
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $isNew ? 'bg-fuchsia-500/20 text-fuchsia-200 ring-1 ring-fuchsia-400/30' : 'bg-white/[.05] text-zinc-400' }}">
                                @if($isNew)<span class="status-ping size-1.5 rounded-full bg-fuchsia-400"></span>@endif
                                {{ $isNew ? 'Nueva' : 'Leída' }}
                            </span>
                        </div>
                        <p class="font-mono-num mt-1 text-[11px] text-zinc-500">{{ $contact->created_at->format('d/m/Y H:i') }}</p>
                    </div>

                    <a class="inline-flex items-center gap-1.5 rounded-full border border-emerald-400/25 bg-emerald-400/10 px-3.5 py-2 text-[11px] font-bold text-emerald-200 transition hover:bg-emerald-400/20"
                       target="_blank" rel="noopener"
                       href="https://wa.me/{{ preg_replace('/\D+/', '', $contact->phone) }}">
                        <x-icon name="whatsapp" class="size-3.5" />
                        Responder por WhatsApp
                        <x-icon name="arrow-up-right" class="size-3.5" />
                    </a>
                </div>

                <p class="mt-4 rounded-2xl border border-white/[.06] bg-white/[.02] p-4 whitespace-pre-line text-sm leading-6 text-zinc-200">{{ $contact->message }}</p>

                <div class="mt-4 flex flex-wrap gap-4 text-xs text-zinc-400">
                    <a href="tel:{{ $contact->phone }}" class="inline-flex items-center gap-1.5 font-mono-num hover:text-white">
                        <x-icon name="phone" class="size-3.5 text-zinc-500" />
                        {{ $contact->phone }}
                    </a>
                    @if($contact->email)
                        <a href="mailto:{{ $contact->email }}" class="inline-flex items-center gap-1.5 hover:text-white">
                            <x-icon name="mail" class="size-3.5 text-zinc-500" />
                            {{ $contact->email }}
                        </a>
                    @endif
                </div>

                <div class="mt-4 flex justify-end gap-3 border-t border-white/[.06] pt-3">
                    <form method="POST" action="{{ route('admin.contacts.update', $contact) }}">
                        @csrf @method('PATCH')
                        <button class="btn-subtle px-3 py-1.5 text-[11px] text-fuchsia-200">
                            {{ $isNew ? 'Marcar como leída' : 'Marcar como nueva' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.contacts.destroy', $contact) }}"
                          data-confirm="¿Eliminar esta consulta de «{{ $contact->name }}»?">
                        @csrf @method('DELETE')
                        <button class="inline-flex items-center gap-1 rounded-xl border border-rose-400/15 bg-rose-500/[.06] px-3 py-1.5 text-[11px] font-bold text-rose-200 transition hover:bg-rose-500/15">
                            <x-icon name="trash" class="size-3.5" />
                            Eliminar
                        </button>
                    </form>
                </div>
            </article>
        @empty
            <div class="panel rounded-3xl p-12 text-center text-sm text-zinc-500 xl:col-span-2">
                Todavía no han llegado consultas. Cuando un cliente complete el formulario del sitio público, aparecerá aquí.
            </div>
        @endforelse
    </div>
    <div class="mt-6">{{ $contacts->links() }}</div>
@endsection
