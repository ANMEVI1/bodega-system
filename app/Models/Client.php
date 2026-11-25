<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Client extends Model
{
    protected $fillable = [
        'name',
        'client_type',
        'document_type',
        'document_number',
        'contact_name',
        'phone',
        'email',
        'address',
        'district',
        'city',
        'preferred_contact',
        'source',
        'status',
        'notes',
        'pending_balance',
    ];

    protected function casts(): array
    {
        return [
            'pending_balance' => 'decimal:2',
        ];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function posSales(): HasMany
    {
        return $this->hasMany(PosSale::class);
    }

    public function latestSale(): HasOne
    {
        return $this->hasOne(Sale::class)->latestOfMany('sold_at');
    }

    public function whatsappPhone(): string
    {
        $digits = preg_replace('/\D+/', '', $this->phone) ?? '';

        return strlen($digits) === 9 && str_starts_with($digits, '9')
            ? '51'.$digits
            : $digits;
    }
}
