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

        if (str_starts_with($lower, 'cliente ')) {
            if (! preg_match('/^cliente\s+(.+?)\s*(?:\|\s*(?:whatsapp|tel[eé]fono):\s*([+\d\s-]+))?$/iu', $message, $matches)) {
                return response()->json(['reply' => 'Usa: Cliente Nombre Apellido | WhatsApp: 51912345678']);
            }
            if (empty($matches[2])) {
                return response()->json(['reply' => 'Para guardar al cliente también necesito su WhatsApp. Usa: Cliente Ana Paz | WhatsApp: 51912345678']);
            }

            $attributes = Validator::make([
                'name' => trim($matches[1]),
                'phone' => preg_replace('/\s+/', '', $matches[2]),
            ], ['name' => ['required', 'string', 'max:160'], 'phone' => ['required', 'string', 'min:8', 'max:32']])->validate();
            Client::query()->create($attributes + ['status' => 'follow-up']);

            return response()->json(['reply' => "Cliente {$attributes['name']} guardado correctamente."]);
        }

        if (str_starts_with($lower, 'producto ')) {
            if (! preg_match('/^producto\s+(.+?)\s*\|\s*precio:\s*(\d+(?:[.,]\d{1,2})?)\s*\|\s*costo:\s*(\d+(?:[.,]\d{1,2})?)\s*\|\s*stock:\s*(\d+(?:[.,]\d{1,2})?)$/iu', $message, $matches)) {
                return response()->json(['reply' => 'Usa: Producto Avena | Precio: 4.50 | Costo: 3.20 | Stock: 12']);
            }

            $attributes = Validator::make([
                'name' => trim($matches[1]),
                'selling_price' => str_replace(',', '.', $matches[2]),
                'cost' => str_replace(',', '.', $matches[3]),
                'stock' => str_replace(',', '.', $matches[4]),
            ], [
                'name' => ['required', 'string', 'max:160'],
                'selling_price' => ['required', 'numeric', 'min:0'],
                'cost' => ['required', 'numeric', 'min:0'],
                'stock' => ['required', 'numeric', 'min:0'],
            ])->validate();
            Product::query()->create($attributes + ['category' => 'Abarrotes', 'unit' => 'unidad', 'low_stock_at' => 5, 'active' => true]);

            return response()->json(['reply' => "Producto {$attributes['name']} guardado en el inventario."]);
        }

        if (str_contains($lower, 'venta') || str_contains($lower, 'resumen')) {
            $monthSales = Sale::query()->where('status', 'paid')->whereDate('sold_at', '>=', now()->startOfMonth());
            $revenue = (float) (clone $monthSales)->sum('total');
            $profit = (float) (clone $monthSales)->selectRaw('COALESCE(SUM(total - quantity * unit_cost), 0) as amount')->value('amount');
            $leader = Product::query()->withSum(['sales as sold' => fn ($query) => $query->where('status', 'paid')->whereDate('sold_at', '>=', now()->startOfMonth())], 'quantity')->orderByDesc('sold')->first();
            $leaderText = $leader && $leader->sold ? " El más vendido es {$leader->name}." : '';

            return response()->json(['reply' => 'Este mes: '.number_format($revenue, 2).' PEN en ventas y '.number_format($profit, 2).' PEN de utilidad después de costos y gastos unitarios.'.$leaderText]);
        }

        return response()->json(['reply' => 'Puedo resumir tus ventas o registrar datos. Escribe "Cliente Nombre | WhatsApp: 51912345678" o "Producto Avena | Precio: 4.50 | Costo: 3.20 | Stock: 12".']);
    }
}
