<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getColorBadgeClassAttribute(): string
    {
        return match ($this->color) {
            'emerald' => 'bg-emerald-500/10 text-emerald-300 border-emerald-500/25',
            'violet' => 'bg-violet-500/10 text-violet-300 border-violet-500/25',
            'amber' => 'bg-amber-500/10 text-amber-300 border-amber-500/25',
            'sky' => 'bg-sky-500/10 text-sky-300 border-sky-500/25',
            'rose' => 'bg-rose-500/10 text-rose-300 border-rose-500/25',
            default => 'bg-fuchsia-500/10 text-fuchsia-300 border-fuchsia-500/25',
        };
    }
}

