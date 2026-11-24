<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SiteSection extends Model
{
    public const DEFAULTS = [
        'home' => [
            'title' => 'Despensa fresca de barrio, lista hoy para tu mesa.',
            'content' => 'Abarrotes esenciales, cafés de Chanchamayo, panes del día y productos seleccionados en Jr. Las Begonias 248, Los Olivos. Consulta precios al instante y arma tu pedido sin colas.',
            'cta_label' => 'Explorar despensa',
            'cta_url' => '#productos',
            'sort_order' => 1,
        ],
        'banner' => [
            'title' => 'Cosecha y abarrotes del día · Selección peruana',
            'content' => 'Reponemos a primera hora cada mañana con productores de Lima y selva central. Precios transparentes actualizados directo desde tienda.',
            'cta_label' => 'Armar pedido hoy',
            'cta_url' => '#contacto',
            'sort_order' => 2,
        ],
        'about' => [
            'title' => 'Más que una bodega, el punto cercano del vecindario',
            'content' => "Desde hace más de doce años acompañamos la mesa diaria de nuestras familias vecinas en Los Olivos.\nSeleccionamos abarrotes de alta rotación, quesos frescos, miel de Oxapampa y pan artesanal de la mañana, combinando trato cercano con stock verificado al instante.",
            'cta_label' => null,
            'cta_url' => null,
            'sort_order' => 3,
        ],
        'contact' => [
            'title' => '¿Necesitas armar tu pedido de hoy?',
            'content' => 'Atendemos consultas rápidas de stock, reservas para recoger en tienda y delivery vecinal en Los Olivos. Escríbenos directo o envíanos tu lista.',
            'cta_label' => 'Enviar consulta ahora',
            'cta_url' => null,
            'sort_order' => 4,
        ],
        'faq' => [
            'title' => 'Preguntas frecuentes',
            'content' => "¿Dónde están ubicados y cuál es el horario? Estamos en Jr. Las Begonias 248, Los Olivos (a media cuadra del parque central). Atendemos todos los días de 7:00 a. m. a 10:00 p. m., incluidos feriados.\n¿Hacen delivery en el barrio? Sí, entregamos en menos de 35 minutos dentro de Los Olivos para pedidos desde S/ 25.00; también puedes separar y recoger sin espera.\n¿Qué medios de pago aceptan? Aceptamos Yape, Plin, tarjetas Visa/Mastercard sin recargo adicional y efectivo en tienda o contra entrega.\n¿El catálogo web muestra precios y stock reales? Sí, tanto la vitrina como nuestro asistente virtual consultan el inventario real del día.",
            'cta_label' => null,
            'cta_url' => null,
            'sort_order' => 5,
        ],
    ];

    protected $fillable = ['section', 'title', 'content', 'cta_label', 'cta_url', 'whatsapp', 'active', 'sort_order'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'sort_order' => 'integer'];
    }

    /**
     * Resolve all editable public sections merged with safe defaults.
     *
     * @return Collection<string, self>
     */
    public static function resolved(): Collection
    {
        $saved = self::query()->get()->keyBy('section');

        return collect(self::DEFAULTS)->map(
            fn (array $defaults, string $key) => $saved->get($key) ?? new self($defaults + ['section' => $key, 'active' => true])
        );
    }

    /**
     * Extract structured Q&A pairs from FAQ section content.
     *
     * @return array<int, array{q: string, a: string}>
     */
    public static function parseFaqLines(?string $content): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $content))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->map(function (string $line): ?array {
                if (preg_match('/^((?:¿)?[^?]+\?)\s*(.+)$/u', $line, $m)) {
                    return ['q' => trim($m[1]), 'a' => trim($m[2])];
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();
    }
}
