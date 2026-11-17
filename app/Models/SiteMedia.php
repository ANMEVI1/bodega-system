<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class SiteMedia extends Model
{
    /**
     * Safe chunk size in characters (~180 KB per SQL row) so that even a default
     * XAMPP installation with max_allowed_packet = 1 MB never fails when uploading
     * or exporting/importing SQL dumps across laptops.
     */
    public const CHUNK_CHAR_LENGTH = 180000;

    protected $table = 'site_media';

    protected $fillable = [
        'section',
        'title',
        'subtitle',
        'badge',
        'cta_label',
        'cta_url',
        'media_type',
        'source_type',
        'external_url',
        'mime_type',
        'file_name',
        'file_size',
        'fit_mode',
        'show_overlay',
        'active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'show_overlay' => 'boolean',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(SiteMediaChunk::class, 'site_media_id')->orderBy('chunk_index');
    }

    public function hasDatabaseBinary(): bool
    {
        if (array_key_exists('chunks_count', $this->attributes)) {
            return (int) $this->attributes['chunks_count'] > 0;
        }

        return $this->chunks()->exists();
    }

    /**
     * Store raw binary content directly in the database in safe base64 chunks.
     */
    public function storeBinary(string $binary, string $mimeType, ?string $fileName = null): void
    {
        try {
            DB::statement('SET GLOBAL max_allowed_packet = 67108864');
        } catch (\Throwable) {
            // Ignore if current DB user lacks SUPER privilege; chunking handles 1MB limits natively.
        }

        $base64 = base64_encode($binary);
        $parts = str_split($base64, self::CHUNK_CHAR_LENGTH);

        $this->chunks()->delete();

        foreach ($parts as $index => $part) {
            SiteMediaChunk::query()->create([
                'site_media_id' => $this->id,
                'chunk_index' => $index,
                'payload' => $part,
            ]);
        }

        $this->forceFill([
            'mime_type' => $mimeType,
            'file_name' => $fileName ?: ($this->file_name ?: 'archivo-multimedia'),
            'file_size' => strlen($binary),
            'updated_at' => now(),
        ])->save();

        $this->setAttribute('chunks_count', count($parts));
    }

    /**
     * Reassemble the binary content stored in the database.
     */
    public function decodedBinary(): ?string
    {
        $payloads = $this->chunks()->orderBy('chunk_index')->pluck('payload')->all();
        if ($payloads === []) {
            return null;
        }

        $decoded = base64_decode(implode('', $payloads), true);

        return $decoded !== false ? $decoded : null;
    }

    /**
     * Detect if the external URL is an embeddable video platform (YouTube / Vimeo).
     */
    public function embedUrl(): ?string
    {
        $url = trim((string) $this->external_url);
        if ($url === '') {
            return null;
        }

        // YouTube watch, short, youtu.be, or embed URLs
        if (preg_match('~(?:youtube\.com/(?:watch\?.*v=|shorts/|embed/|live/)|youtu\.be/)([A-Za-z0-9_-]{6,15})~i', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1].'?rel=0&modestbranding=1';
        }

        // Vimeo URLs
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~i', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return null;
    }

    /**
     * Resolve the public URL to render this image or video.
     * Prioritizes database-stored binary when source_type is 'upload' OR when a DB backup exists for an image URL.
     */
    public function mediaUrl(): ?string
    {
        if ($this->source_type === 'upload' && $this->hasDatabaseBinary()) {
            return route('site.media', ['media' => $this->id, 'v' => $this->updated_at?->timestamp ?: 1]);
        }

        if ($this->source_type === 'url') {
            if ($this->embedUrl()) {
                return $this->embedUrl();
            }

            // If we cached a web image into the database, serve from DB so it works offline/across laptops too
            if ($this->media_type === 'image' && $this->hasDatabaseBinary()) {
                return route('site.media', ['media' => $this->id, 'v' => $this->updated_at?->timestamp ?: 1]);
            }

            if (! empty($this->external_url)) {
                return $this->external_url;
            }
        }

        if ($this->hasDatabaseBinary()) {
            return route('site.media', ['media' => $this->id, 'v' => $this->updated_at?->timestamp ?: 1]);
        }

        return $this->external_url ?: null;
    }

    public function formattedSize(): string
    {
        $bytes = (int) ($this->file_size ?? 0);
        if ($bytes <= 0) {
            return '';
        }
        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return number_format($bytes / (1024 * 1024), 2).' MB';
    }
}
