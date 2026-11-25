<?php

namespace App\Http\Controllers;

use App\Exceptions\PosAiException;
use App\Models\Client;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\Product;
use App\Models\Sale;
use App\Services\PosAiService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PosController extends Controller
{
    public function show(): View
    {
        return view('site.pos', [
            'businessName' => config('app.name', 'Bodega Los Olivos'),
            'currency' => 'PEN',
            'homeUrl' => route('home'),
        ]);
    }

    public function catalogo(): JsonResponse
    {
        $products = Product::query()
            ->with('categoryRelation')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $productos = $products->map(function (Product $product): array {
            $categoria = $product->categoryRelation?->name ?: ($product->category ?: 'General');
            $stockActual = (float) $product->stock;
            $stockMinimo = (float) $product->low_stock_at;

            return [
                'id' => $product->id,
                'codigoSku' => $product->sku ?? '',
                'nombre' => $product->name,
                'categoria' => $categoria,
                'precioCompra' => round((float) $product->cost, 2),
                'precioVenta' => round((float) $product->selling_price, 2),
                'stockActual' => $stockActual,
                'stockMinimo' => $stockMinimo,
                'unidadMedida' => $product->unit ?: 'unidad',
                'bajoStock' => $stockActual <= $stockMinimo,
                'creadoEn' => $product->created_at?->toISOString(),
                'actualizadoEn' => $product->updated_at?->toISOString(),
            ];
        })->values()->all();

        $categorias = $this->ordenarCategorias(
            collect($productos)->pluck('categoria')->filter()->unique()->values()->all()
        );

        return response()->json([
            'data' => [
                'productos' => $productos,
                'categorias' => $categorias,
            ],
        ]);
    }

    public function clientes(): JsonResponse
    {
        $total = Client::query()->count();
        $clients = Client::query()
            ->orderBy('name')
            ->limit(100)
            ->get();

        $data = $clients->map(fn (Client $client): array => [
            'id' => $client->id,
            'nombre' => $client->name,
            'telefono' => $client->phone !== '' ? $client->phone : null,
            'documento' => $client->document_number,
            'notas' => $client->notes,
            'saldoPendiente' => round((float) ($client->pending_balance ?? 0), 2),
            'creadoEn' => $client->created_at?->toISOString(),
        ])->values()->all();

        return response()->json([
            'data' => $data,
            'meta' => [
                'page' => 1,
                'pageSize' => 100,
                'total' => $total,
            ],
        ]);
    }

    public function aiCart(Request $request, PosAiService $ai): JsonResponse
    {
        $data = $request->validate([
            'mensaje' => ['required', 'string', 'min:1', 'max:1000'],
        ]);

        try {
            $result = $ai->fillCartFromMessage($data['mensaje']);

            return response()->json(['data' => $result], 200);
        } catch (PosAiException $e) {
            return response()->json([
                'error' => [
                    'code' => $e->errorCode,
                    'message' => $e->getMessage(),
                ],
            ], $e->statusCode);
        }
    }

    public function checkout(Request $request, PosAiService $ai): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.productoId' => ['nullable', 'integer', 'min:1'],
            'items.*.nombreProducto' => ['nullable', 'string', 'max:150'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'items.*.precioUnitario' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'metodoPago' => ['nullable', 'in:EFECTIVO,YAPE,PLIN,TRANSFERENCIA,TARJETA,FIADO'],
            'clienteId' => ['nullable', 'integer', 'exists:clients,id'],
            'clienteNombre' => ['nullable', 'string', 'max:150'],
            'montoRecibido' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'origen' => ['nullable', 'string', 'max:20'],
        ]);

        $metodoPago = $data['metodoPago'] ?? 'EFECTIVO';
        $origenInput = strtoupper(trim((string) ($data['origen'] ?? 'MANUAL')));
        $origen = in_array($origenInput, ['MANUAL', 'VOZ', 'IA'], true) ? $origenInput : 'MANUAL';
        $clienteId = $data['clienteId'] ?? null;
        $clienteNombre = isset($data['clienteNombre']) ? trim((string) $data['clienteNombre']) : '';
        $montoRecibidoEnviado = $request->has('montoRecibido') && $request->input('montoRecibido') !== null && $request->input('montoRecibido') !== '';

        $resultado = DB::transaction(function () use ($data, $metodoPago, $origen, $clienteId, $clienteNombre, $montoRecibidoEnviado): array {
            if ($metodoPago === 'FIADO' && empty($clienteId) && $clienteNombre === '') {
                $this->failCheckout(422, 'CLIENTE_REQUERIDO', 'Selecciona o ingresa un cliente para registrar una venta fiada.');
            }

            $client = $this->resolverCliente($clienteId, $clienteNombre);
            $resolvedItems = [];
            $touchedProductIds = [];
            $total = 0.0;

            foreach ($data['items'] as $item) {
                $cantidad = round((float) $item['cantidad'], 3);
                $productoId = isset($item['productoId']) && $item['productoId'] !== null ? (int) $item['productoId'] : null;

                if ($productoId !== null) {
                    $product = Product::query()->lockForUpdate()->findOrFail($productoId);

                    if (! $product->active || (float) $product->stock < $cantidad) {
                        $mensajeStock = ! $product->active
                            ? "El producto {$product->name} no está activo para la venta."
                            : "Stock insuficiente para {$product->name} (disponible: ".(float) $product->stock.').';

                        $this->failCheckout(409, 'STOCK_INSUFICIENTE', $mensajeStock);
                    }

                    $unitPrice = array_key_exists('precioUnitario', $item) && $item['precioUnitario'] !== null
                        ? round((float) $item['precioUnitario'], 2)
                        : round((float) $product->selling_price, 2);
                    $subtotal = round($unitPrice * $cantidad, 2);

                    $product->decrement('stock', $cantidad);

                    Sale::query()->create([
                        'reference' => 'NV-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
                        'product_id' => $product->id,
                        'client_id' => $client?->id,
                        'quantity' => $cantidad,
                        'unit_price' => $unitPrice,
                        'unit_cost' => round((float) $product->cost + (float) $product->unit_expense, 2),
                        'total' => $subtotal,
                        'sold_at' => today(),
                        'status' => $metodoPago === 'FIADO' ? 'pending' : 'paid',
                    ]);

                    $touchedProductIds[] = $product->id;
                    $resolvedItems[] = [
                        'product_id' => $product->id,
                        'product_name' => Str::limit($product->name, 150, ''),
                        'quantity' => $cantidad,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                    ];
                } else {
                    $nombreLibre = trim((string) ($item['nombreProducto'] ?? ''));
                    if ($nombreLibre === '' || ! array_key_exists('precioUnitario', $item) || $item['precioUnitario'] === null) {
                        $this->failCheckout(422, 'DATOS_ITEM_INVALIDOS', 'Los ítems sin productoId requieren nombreProducto y precioUnitario.');
                    }

                    $unitPrice = round((float) $item['precioUnitario'], 2);
                    $subtotal = round($unitPrice * $cantidad, 2);

                    $resolvedItems[] = [
                        'product_id' => null,
                        'product_name' => Str::limit($nombreLibre, 150, ''),
                        'quantity' => $cantidad,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                    ];
                }

                $total += $subtotal;
            }

            $total = round($total, 2);

            if ($metodoPago === 'EFECTIVO') {
                $montoRecibido = $montoRecibidoEnviado ? round((float) $data['montoRecibido'], 2) : $total;
                if ($montoRecibido < $total) {
                    $this->failCheckout(422, 'MONTO_INSUFICIENTE', 'El monto recibido es menor al total de la venta.');
                }
                $vuelto = round(max(0, $montoRecibido - $total), 2);
            } else {
                $montoRecibido = $total;
                $vuelto = 0.0;
            }

            $posSale = PosSale::query()->create([
                'reference' => 'POS-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
                'client_id' => $client?->id,
                'total' => $total,
                'payment_method' => $metodoPago,
                'amount_received' => $montoRecibido,
                'change_amount' => $vuelto,
                'origin' => $origen,
                'sold_at' => now(),
            ]);

            foreach ($resolvedItems as $itemData) {
                $posSale->items()->create($itemData);
            }

            if ($metodoPago === 'FIADO' && $client !== null) {
                $client->increment('pending_balance', $total);
                $client->refresh();
            }

            $alertasStock = $touchedProductIds === []
                ? []
                : Product::query()
                    ->whereIn('id', array_unique($touchedProductIds))
                    ->get()
                    ->filter(fn (Product $p): bool => (float) $p->stock <= (float) $p->low_stock_at)
                    ->map(fn (Product $p): array => [
                        'productoId' => $p->id,
                        'nombre' => $p->name,
                        'stockActual' => (float) $p->stock,
                        'stockMinimo' => (float) $p->low_stock_at,
                        'unidadMedida' => $p->unit ?: 'unidad',
                    ])
                    ->values()
                    ->all();

            $posSale->load('items');

            return [
                'posSale' => $posSale,
                'client' => $client,
                'alertasStock' => $alertasStock,
                'total' => $total,
                'montoRecibido' => $montoRecibido,
                'vuelto' => $vuelto,
            ];
        });

        /** @var PosSale $posSale */
        $posSale = $resultado['posSale'];
        /** @var Client|null $client */
        $client = $resultado['client'];
        $total = $resultado['total'];
        $montoRecibido = $resultado['montoRecibido'];
        $vuelto = $resultado['vuelto'];

        $mensajeVoz = $this->construirMensajeVozCheckout($ai, $metodoPago, $total, $vuelto, $client);

        return response()->json([
            'data' => [
                'venta' => [
                    'id' => $posSale->id,
                    'referencia' => $posSale->reference,
                    'clienteId' => $posSale->client_id,
                    'clienteNombre' => $client?->name,
                    'total' => (float) $posSale->total,
                    'metodoPago' => $posSale->payment_method,
                    'montoRecibido' => (float) $posSale->amount_received,
                    'vuelto' => (float) $posSale->change_amount,
                    'origen' => $posSale->origin,
                    'fecha' => $posSale->sold_at?->toISOString(),
                    'items' => $posSale->items->map(fn (PosSaleItem $item): array => [
                        'id' => $item->id,
                        'productoId' => $item->product_id,
                        'nombreProducto' => $item->product_name,
                        'cantidad' => (float) $item->quantity,
                        'precioUnitario' => (float) $item->unit_price,
                        'subtotal' => (float) $item->subtotal,
                    ])->values()->all(),
                ],
                'alertasStock' => $resultado['alertasStock'],
                'pago' => [
                    'metodoPago' => $metodoPago,
                    'total' => $total,
                    'montoRecibido' => $montoRecibido,
                    'vuelto' => $vuelto,
                    'saldoPendienteCliente' => $client ? round((float) ($client->pending_balance ?? 0), 2) : null,
                ],
                'mensaje_voz' => $mensajeVoz,
            ],
        ], 201);
    }

    private function resolverCliente(?int $clienteId, string $clienteNombre): ?Client
    {
        if ($clienteId !== null) {
            return Client::query()->lockForUpdate()->findOrFail($clienteId);
        }

        if ($clienteNombre === '') {
            return null;
        }

        $existente = Client::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($clienteNombre, 'UTF-8')])
            ->lockForUpdate()
            ->first();

        if ($existente !== null) {
            return $existente;
        }

        return Client::query()->create([
            'name' => $clienteNombre,
            'phone' => '',
            'status' => 'active',
            'source' => 'pos',
        ]);
    }

    private function failCheckout(int $status, string $code, string $message): never
    {
        throw new HttpResponseException(
            response()->json([
                'error' => [
                    'code' => $code,
                    'message' => $message,
                ],
            ], $status)
        );
    }

    private function construirMensajeVozCheckout(
        PosAiService $ai,
        string $metodoPago,
        float $total,
        float $vuelto,
        ?Client $client
    ): string {
        $totalVoz = $ai->montoEnVoz($total);

        if ($metodoPago === 'FIADO' && $client !== null) {
            return "Venta al fiado registrada para {$client->name} por {$totalVoz}.";
        }

        $base = "Venta registrada por {$totalVoz} con ".strtolower($metodoPago).'.';

        if ($metodoPago === 'EFECTIVO' && $vuelto > 0) {
            $base .= ' Vuelto: '.$ai->montoEnVoz($vuelto).'.';
        }

        return $base;
    }

    /**
     * @param  array<int, string>  $categorias
     * @return array<int, string>
     */
    private function ordenarCategorias(array $categorias): array
    {
        if (class_exists(\Collator::class)) {
            $collator = new \Collator('es');
            $collator->sort($categorias);

            return array_values($categorias);
        }

        $fold = static fn (string $value): string => strtr(mb_strtolower($value, 'UTF-8'), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'nz',
        ]);

        usort($categorias, static fn (string $a, string $b): int => strnatcasecmp($fold($a), $fold($b)));

        return array_values($categorias);
    }
}
