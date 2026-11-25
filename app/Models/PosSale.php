<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSale extends Model
{
    protected $fillable = [
        'reference',
        'client_id',
        'total',
        'payment_method',
        'amount_received',
        'change_amount',
        'origin',
        'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'amount_received' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'sold_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosSaleItem::class);
    }
}
