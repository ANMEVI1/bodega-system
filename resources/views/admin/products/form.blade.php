@extends('layouts.admin')
@section('title', $product->exists ? 'Editar producto' : 'Agregar producto')
@section('heading', $product->exists ? 'Editar producto' : 'Nuevo producto')
@section('content')
    <div class="mx-auto max-w-4xl" data-cinema-enter>
        <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-zinc-400 transition hover:text-white">
            <x-icon name="arrow-left" class="size-4" />
            Volver a productos
        </a>

        <form method="POST" data-price-calc
              action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
              class="panel mt-5 space-y-6 rounded-3xl p-6 sm:p-8">
            @csrf @if($product->exists) @method('PUT') @endif

            <div>
                <p class="text-[11px] font-extrabold uppercase tracking-[.18em] text-fuchsia-300">Catálogo maestro de productos</p>
                <h1 class="mt-1 text-2xl font-extrabold text-white">{{ $product->exists ? 'Editar producto' : 'Nuevo producto' }}</h1>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="text-xs font-bold text-zinc-300">
                    Nombre del producto
                    <input class="field mt-2" name="name" id="product-name" value="{{ old('name', $product->name) }}" maxlength="160" required placeholder="Ej. Arroz Extra Costeño 5 kg">
                </label>
                <div>
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-zinc-300">SKU / Código (opcional)</label>
                        <button type="button" onclick="generateSku()" class="text-[11px] font-semibold text-fuchsia-400 hover:text-fuchsia-300 transition">Generar código</button>
                    </div>
                    <input class="field font-mono-num mt-2" name="sku" id="product-sku" value="{{ old('sku', $product->sku) }}" maxlength="80" placeholder="Ej. ABR-10293">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-zinc-300">Categoría</label>
                        <a href="{{ route('admin.categories.create') }}" class="text-[11px] font-semibold text-fuchsia-400 hover:text-fuchsia-300 transition">+ Nueva</a>
                    </div>
                    <select class="field mt-2" name="category_id">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id) == $cat->id || (!old('category_id') && old('category', $product->category) === $cat->name))>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <label class="text-xs font-bold text-zinc-300">
                    Unidad
                    <input class="field mt-2" name="unit" value="{{ old('unit', $product->unit ?: 'unidad') }}" maxlength="40" required placeholder="unidad, kg, litro...">
                </label>
                <label class="text-xs font-bold text-zinc-300">
                    Stock actual
                    <input class="field font-mono-num mt-2" name="stock" type="number" step="0.01" min="0" value="{{ old('stock', $product->stock ?? 0) }}" required>
                </label>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="text-xs font-bold text-zinc-300">
                    Costo de compra unitario (S/)
                    <input class="field font-mono-num mt-2" name="cost" type="number" step="0.01" min="0" value="{{ old('cost', $product->cost ?? 0) }}" required>
                </label>
                <label class="text-xs font-bold text-zinc-300">
                    Otros gastos por unidad (S/)
                    <input class="field font-mono-num mt-2" name="unit_expense" type="number" step="0.01" min="0" value="{{ old('unit_expense', $product->unit_expense ?? 0) }}">
                </label>
                <label class="text-xs font-bold text-zinc-300">
                    Margen objetivo sobre venta (%)
                    <input class="field font-mono-num mt-2" name="margin" type="number" step="0.1" min="0" max="95" value="{{ old('margin', $product->exists && $product->selling_price > 0 ? round((1 - ($product->cost + $product->unit_expense) / $product->selling_price) * 100, 1) : 25) }}" required>
                </label>
                <label class="text-xs font-bold text-zinc-300">
                    Alerta de stock bajo
                    <input class="field font-mono-num mt-2" name="low_stock_at" type="number" step="0.01" min="0" value="{{ old('low_stock_at', $product->low_stock_at ?? 5) }}" required>
                </label>
            </div>

            {{-- Live Pricing Preview Widget --}}
            <div class="rounded-2xl border border-fuchsia-400/20 bg-gradient-to-br from-fuchsia-500/[.08] via-violet-500/[.04] to-transparent p-5">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-[.18em] text-fuchsia-300">Simulación en tiempo real</p>
                        <p class="mt-1 text-xs text-zinc-400">Precio recomendado = (costo + gastos/u) ÷ (1 − margen)</p>
                    </div>
                    <div class="flex flex-wrap gap-6">
                        <div>
                            <p class="text-[10px] text-zinc-500">Costo total / u</p>
                            <p id="calc-total-cost" class="font-mono-num text-sm font-bold text-zinc-200">S/ 0.00</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-zinc-500">Ganancia / u</p>
                            <p id="calc-unit-profit" class="font-mono-num text-sm font-bold text-emerald-300">S/ 0.00</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-fuchsia-200">Precio sugerido</p>
                            <p id="calc-selling-price" class="font-mono-num text-xl font-extrabold text-white">S/ 0.00</p>
                        </div>
                    </div>
                </div>
                <div class="mt-4 h-1.5 w-full overflow-hidden rounded-full bg-white/[.08]">
                    <div id="calc-margin-bar" class="h-full w-1/4 rounded-full bg-gradient-to-r from-fuchsia-400 to-emerald-400 transition-all duration-300"></div>
                </div>
            </div>

            <div class="space-y-3 pt-2">
                <label class="flex items-center gap-3 text-xs font-bold text-zinc-300">
                    <input class="size-4 rounded accent-fuchsia-500" type="checkbox" name="active" value="1" @checked(old('active', $product->exists ? $product->active : true))>
                    <span>Producto activo (disponible para operaciones de inventario y registro de ventas)</span>
                </label>

                <label class="flex items-start gap-3 text-xs font-bold text-zinc-400">
                    <input class="mt-0.5 size-4 rounded accent-fuchsia-500" type="checkbox" name="is_public" value="1" @checked(old('is_public', $product->exists ? $product->is_public : false))>
                    <div>
                        <span class="text-zinc-200">Mostrar también en el catálogo de la página web pública</span>
                        <p class="text-[11px] font-normal text-zinc-500">Por defecto está desactivado. Si no lo marcas, este producto <strong>solo existirá en tu panel de administración</strong>.</p>
                    </div>
                </label>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button class="btn-primary text-xs">{{ $product->exists ? 'Guardar cambios' : 'Guardar producto' }}</button>
                <a href="{{ route('admin.products.index') }}" class="btn-subtle text-xs">Cancelar</a>
            </div>
        </form>
    </div>

    <script>
        function generateSku() {
            const name = document.getElementById('product-name')?.value || 'PRD';
            const clean = name.replace(/[^a-zA-Z]/g, '').substring(0, 3).toUpperCase() || 'PRD';
            const random = Math.floor(1000 + Math.random() * 9000);
            const skuInput = document.getElementById('product-sku');
            if (skuInput) {
                skuInput.value = clean + '-' + random;
            }
        }
    </script>
@endsection
