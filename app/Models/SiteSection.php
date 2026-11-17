<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSection extends Model
{
    protected $fillable = ['section', 'title', 'content', 'cta_label', 'cta_url', 'whatsapp', 'active', 'sort_order'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'sort_order' => 'integer'];
    }
}
