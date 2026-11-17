<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    public const STATUSES = [
        'active' => 'Activo',
        'paused' => 'En pausa',
    ];

    public const PAYMENT_TERMS = [
        'contado' => 'Contado',
        'contraentrega' => 'Contraentrega',
        'credito_15' => 'Crédito 15 días',
        'credito_30' => 'Crédito 30 días',
        'credito_60' => 'Crédito 60 días',
    ];

    public const WHATSAPP_TEMPLATES = [
        'disponibilidad' => [
            'label' => 'Consultar disponibilidad y precios',
            'description' => 'Ideal para verificar stock antes de emitir pedido.',
            'message' => 'Hola :supplier, te escribo de Bodega Los Olivos para consultar disponibilidad y precios vigentes.',
        ],
        'cotizacion' => [
            'label' => 'Solicitar cotización actualizada',
            'description' => 'Pide lista de precios o descuento por volumen.',
            'message' => 'Hola :supplier, ¿nos podrías compartir una cotización actualizada para nuestro próximo pedido en Bodega Los Olivos?',
        ],
        'despacho' => [
            'label' => 'Coordinar despacho / entrega',
            'description' => 'Confirma fecha, horario y dirección de entrega.',
            'message' => 'Hola :supplier, te escribimos de Bodega Los Olivos para coordinar el horario de despacho de nuestro pedido.',
        ],
        'pago' => [
            'label' => 'Confirmar comprobante o pago',
            'description' => 'Seguimiento de facturas, guías o condición comercial.',
            'message' => 'Hola :supplier, te contactamos de Bodega Los Olivos para confirmar el comprobante y estado de nuestra última operación.',
        ],
        'personalizado' => [
            'label' => 'Mensaje personalizado',
            'description' => 'Redacta libremente tu mensaje antes de enviarlo.',
            'message' => 'Hola :supplier, te escribo de Bodega Los Olivos. ',
        ],
    ];

    protected $fillable = [
        'name',
        'tax_id',
        'contact_name',
        'phone',
        'email',
        'address',
        'specialty',
        'payment_terms',
        'status',
        'whatsapp_template',
        'notes',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(SupplierPurchase::class);
    }

    public function whatsappDigits(): string
    {
        return preg_replace('/\D+/', '', (string) $this->phone) ?: '';
    }

    public function whatsappUrl(?string $message = null): string
    {
        $digits = $this->whatsappDigits();
        $defaultText = "Hola {$this->name}, te escribo de Bodega Los Olivos para consultar disponibilidad y precios.";
        $text = urlencode($message ?: $defaultText);

        return "https://wa.me/{$digits}?text={$text}";
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $letters = collect($words)
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');

        return $letters !== '' ? $letters : 'PR';
    }

    public function statusLabel(): string
    {
        $status = $this->status ?: 'active';

        return self::STATUSES[$status] ?? 'Activo';
    }

    public function paymentTermsLabel(): ?string
    {
        if (! $this->payment_terms) {
            return null;
        }

        return self::PAYMENT_TERMS[$this->payment_terms] ?? $this->payment_terms;
    }

    public function defaultWhatsappTemplateKey(): string
    {
        if ($this->whatsapp_template && array_key_exists($this->whatsapp_template, self::WHATSAPP_TEMPLATES)) {
            return $this->whatsapp_template;
        }

        return 'disponibilidad';
    }

    /**
     * @return array<string, array{key: string, label: string, description: string, message: string}>
     */
    public function resolvedWhatsappTemplates(): array
    {
        $recipient = $this->contact_name ? "{$this->contact_name} ({$this->name})" : $this->name;
        $resolved = [];

        foreach (self::WHATSAPP_TEMPLATES as $key => $tpl) {
            $resolved[$key] = [
                'key' => $key,
                'label' => $tpl['label'],
                'description' => $tpl['description'],
                'message' => str_replace(':supplier', $recipient, $tpl['message']),
            ];
        }

        return $resolved;
    }
}
