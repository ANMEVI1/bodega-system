<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteMediaChunk extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'site_media_id',
        'chunk_index',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'site_media_id' => 'integer',
            'chunk_index' => 'integer',
        ];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(SiteMedia::class, 'site_media_id');
    }
}
