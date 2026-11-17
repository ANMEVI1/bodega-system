<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $type = $request->query('type');
        abort_unless(in_array($type, ['sales', 'clients', 'products'], true), 404);
        $filename = $type.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($type): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            match ($type) {
                'sales' => $this->writeSales($output),
                'clients' => $this->writeClients($output),
                'products' => $this->writeProducts($output),
            };

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function writeSales($output): void
    {
        fputcsv($output, ['Referencia', 'Fecha', 'Cliente', 'Producto', 'Cantidad', 'Precio unitario', 'Costo y gasto unitario', 'Total', 'Estado']);
        Sale::query()->with(['product', 'client'])->orderBy('sold_at')->chunk(500, function ($sales) use ($output): void {
            foreach ($sales as $sale) {
                $this->writeRow($output, [$sale->reference, $sale->sold_at->format('Y-m-d'), $sale->client?->name ?? 'Público', $sale->product->name, $sale->quantity, $sale->unit_price, $sale->unit_cost, $sale->total, $sale->status]);
            }
        });
    }

    private function writeClients($output): void
    {
        fputcsv($output, ['Nombre', 'WhatsApp', 'Correo', 'Estado', 'Total comprado']);
        Client::query()->withSum('sales as total_spent', 'total')->orderBy('name')->chunk(500, function ($clients) use ($output): void {
            foreach ($clients as $client) {
                $this->writeRow($output, [$client->name, $client->phone, $client->email, $client->status, $client->total_spent ?? 0]);
            }
        });
    }

    private function writeProducts($output): void
    {
        fputcsv($output, ['Producto', 'SKU', 'Categoría', 'Costo de compra', 'Gasto unitario', 'Precio', 'Stock', 'Vendidos', 'Activo']);
        Product::query()->withSum(['sales as units_sold' => fn ($query) => $query->where('status', 'paid')], 'quantity')->orderBy('name')->chunk(500, function ($products) use ($output): void {
            foreach ($products as $product) {
                $this->writeRow($output, [$product->name, $product->sku, $product->category, $product->cost, $product->unit_expense, $product->selling_price, $product->stock, $product->units_sold ?? 0, $product->active ? 'Sí' : 'No']);
            }
        });
    }

    private function writeRow($output, array $values): void
    {
        fputcsv($output, array_map(function ($value) {
            if (is_string($value) && preg_match('/^\s*[=+\-@]/', $value)) {
                return "'".$value;
            }

            return $value;
        }, $values));
    }
}
