<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:500']]);
        $message = trim($data['message']);
        $lower = mb_strtolower($message);

        if (
            str_starts_with($lower, 'cliente ') ||
            str_starts_with($lower, 'cliente:') ||
            str_starts_with($lower, 'registrar cliente')
        ) {
            return $this->handleClientCommand($message);
        }

        if (
            str_starts_with($lower, 'producto ') ||
            str_starts_with($lower, 'producto:') ||
            str_starts_with($lower, 'registrar producto')
        ) {
            return $this->handleProductCommand($message);
        }

        if (str_contains($lower, 'venta') || str_contains($lower, 'resumen')) {
            return $this->handleSalesSummary();
        }

        return response()->json([
            'reply' => 'Puedo resumir tus ventas o registrar datos. Usa los chips rápidos para insertar la plantilla de cliente o producto.',
        ]);
    }

    private function handleClientCommand(string $message): JsonResponse
    {
        $pattern = '/^(?:registrar\s+)?cliente:?\s*(.+?)\s*(?:\|\s*(?:whatsapp|tel[eé]fono):?\s*([^|]+?))?(?:\s*\|\s*(?:correo|email):?\s*(.+))?$/iu';

        if (! preg_match($pattern, $message, $matches)) {
            return response()->json([
                'reply' => 'Usa: Registrar cliente: Nombre Apellido | Teléfono: 999999999 | Correo: correo@ejemplo.com',
            ]);
        }

        $rawName = $this->cleanBracketValue($matches[1] ?? '');
        $rawPhone = $this->cleanBracketValue($matches[2] ?? '');
        $rawEmail = $this->cleanBracketValue($matches[3] ?? '');

        if ($rawName === '' || mb_strtolower($rawName) === 'nombre completo') {
            return response()->json([
                'reply' => 'Por favor reemplaza [Nombre completo] con el nombre real del cliente.',
            ]);
        }

        if ($rawPhone === '' || $rawPhone === '999999999') {
            return response()->json([
                'reply' => 'Para guardar al cliente también necesito su teléfono o WhatsApp válido. Usa: Registrar cliente: Ana Paz | Teléfono: 51912345678 | Correo: ana@ejemplo.com',
            ]);
        }

        $email = ($rawEmail !== '' && mb_strtolower($rawEmail) !== 'correo@ejemplo.com') ? $rawEmail : null;

        $attributes = Validator::make([
            'name' => $rawName,
            'phone' => preg_replace('/\s+/', '', $rawPhone),
            'email' => $email,
        ], [
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'min:8', 'max:32'],
            'email' => ['nullable', 'email', 'max:160'],
        ])->validate();

        Client::query()->create($attributes + ['status' => 'follow-up']);

        return response()->json([
            'reply' => "Cliente {$attributes['name']} guardado correctamente.",
            'action_completed' => 'client_created',
        ]);
    }

    private function handleProductCommand(string $message): JsonResponse
    {
        $pattern = '/^(?:registrar\s+)?producto:?\s*(.+?)\s*\|\s*precio:?\s*\[?(\d+(?:[.,]\d{1,2})?)\]?\s*\|\s*costo:?\s*\[?(\d+(?:[.,]\d{1,2})?)\]?\s*\|\s*stock:?\s*\[?(\d+(?:[.,]\d{1,2})?)\]?$/iu';

        if (! preg_match($pattern, $message, $matches)) {
            return response()->json([
                'reply' => 'Usa: Registrar producto: Avena | Precio: 4.50 | Costo: 3.20 | Stock: 12',
            ]);
        }

        $rawName = $this->cleanBracketValue($matches[1] ?? '');

        if ($rawName === '' || mb_strtolower($rawName) === 'nombre') {
            return response()->json([
                'reply' => 'Por favor reemplaza [Nombre] con el nombre real del producto.',
            ]);
        }

        $attributes = Validator::make([
            'name' => $rawName,
            'selling_price' => str_replace(',', '.', $matches[2]),
            'cost' => str_replace(',', '.', $matches[3]),
            'stock' => str_replace(',', '.', $matches[4]),
        ], [
            'name' => ['required', 'string', 'max:160'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'cost' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'numeric', 'min:0'],
        ])->validate();

        Product::query()->create($attributes + [
            'category' => 'Abarrotes',
            'unit' => 'unidad',
            'low_stock_at' => 5,
            'active' => true,
        ]);

        return response()->json([
            'reply' => "Producto {$attributes['name']} guardado en el inventario.",
            'action_completed' => 'product_created',
        ]);
    }

    private function handleSalesSummary(): JsonResponse
    {
        $monthSales = Sale::query()
            ->where('status', 'paid')
            ->whereDate('sold_at', '>=', now()->startOfMonth());

        $revenue = (float) (clone $monthSales)->sum('total');
        $profit = (float) (clone $monthSales)
            ->selectRaw('COALESCE(SUM(total - quantity * unit_cost), 0) as amount')
            ->value('amount');

        $leader = Product::query()
            ->withSum([
                'sales as sold' => fn ($query) => $query
                    ->where('status', 'paid')
                    ->whereDate('sold_at', '>=', now()->startOfMonth()),
            ], 'quantity')
            ->orderByDesc('sold')
            ->first();

        $hasLeader = $leader && (float) $leader->sold > 0;
        $leaderText = $hasLeader ? " El más vendido es {$leader->name}." : '';

        return response()->json([
            'reply' => 'Este mes: '.number_format($revenue, 2).' PEN en ventas y '.number_format($profit, 2).' PEN de utilidad después de costos y gastos unitarios.'.$leaderText,
            'type' => 'metrics_card',
            'metrics' => [
                [
                    'label' => 'Total Vendido',
                    'value' => 'S/ '.number_format($revenue, 2),
                    'tone' => 'purple',
                ],
                [
                    'label' => 'Ganancia Neta',
                    'value' => 'S/ '.number_format($profit, 2),
                    'tone' => 'emerald',
                ],
                [
                    'label' => 'Producto Top',
                    'value' => $hasLeader ? $leader->name : 'Sin ventas aún',
                    'subvalue' => $hasLeader
                        ? number_format((float) $leader->sold, 0).' uds. este mes'
                        : 'Registra tu primera venta del mes',
                    'tone' => 'violet',
                    'full_width' => true,
                ],
            ],
        ]);
    }

    private function cleanBracketValue(string $value): string
    {
        return trim($value, " \t\n\r\0\x0B[]");
    }
}
