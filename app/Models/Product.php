<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['name', 'sku', 'category', 'category_id', 'unit', 'cost', 'unit_expense', 'selling_price', 'stock', 'low_stock_at', 'active', 'is_public'];

    protected function casts(): array
    {
        return ['cost' => 'decimal:2', 'unit_expense' => 'decimal:2', 'selling_price' => 'decimal:2', 'stock' => 'decimal:2', 'low_stock_at' => 'decimal:2', 'active' => 'boolean', 'is_public' => 'boolean'];
    }

    public function categoryRelation(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function supplierPurchases(): HasMany
    {
        return $this->hasMany(SupplierPurchase::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
