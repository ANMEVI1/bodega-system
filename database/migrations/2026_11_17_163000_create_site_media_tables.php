<?php

use App\Models\SiteMedia;
use App\Models\SiteSection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_media')) {
            Schema::create('site_media', function (Blueprint $table) {
                $table->id();
                $table->string('section', 40)->default('promo')->index();
                $table->string('title', 160)->nullable();
                $table->text('subtitle')->nullable();
                $table->string('badge', 80)->nullable();
                $table->string('cta_label', 80)->nullable();
                $table->string('cta_url', 255)->nullable();
                $table->string('media_type', 20)->default('image'); // image | video
                $table->string('source_type', 20)->default('upload'); // upload | url
                $table->text('external_url')->nullable();
                $table->string('mime_type', 100)->nullable();
                $table->string('file_name', 255)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('fit_mode', 20)->default('cover'); // cover | contain | auto
                $table->boolean('show_overlay')->default(true);
                $table->boolean('active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('site_media_chunks')) {
            Schema::create('site_media_chunks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_media_id')->constrained('site_media')->cascadeOnDelete();
                $table->unsignedInteger('chunk_index')->default(0);
                $table->mediumText('payload');
                $table->index(['site_media_id', 'chunk_index']);
            });
        }

        // Seed default promo section & default DB-stored promotional artwork if none exists
        if (SiteSection::query()->where('section', 'promo')->doesntExist()) {
            SiteSection::query()->create([
                'section' => 'promo',
                'title' => 'Promociones y presentación de temporada',
                'content' => 'Descubre nuestras ofertas del día, productos frescos y novedades pensadas para tu familia.',
                'cta_label' => 'Ver catálogo de ofertas',
                'cta_url' => '#productos',
                'active' => true,
                'sort_order' => 0,
            ]);
        }

        if (SiteMedia::query()->where('section', 'promo')->doesntExist()) {
            $defaultSvg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 540" width="1440" height="540" fill="none">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1440" y2="540" gradientUnits="userSpaceOnUse">
      <stop offset="0%" stop-color="#192814"/>
      <stop offset="48%" stop-color="#263B1D"/>
      <stop offset="100%" stop-color="#142010"/>
    </linearGradient>
    <radialGradient id="glow1" cx="78%" cy="32%" r="48%">
      <stop offset="0%" stop-color="#D7F0B4" stop-opacity="0.34"/>
      <stop offset="100%" stop-color="#D7F0B4" stop-opacity="0"/>
    </radialGradient>
    <radialGradient id="glow2" cx="22%" cy="80%" r="45%">
      <stop offset="0%" stop-color="#D1C3F6" stop-opacity="0.28"/>
      <stop offset="100%" stop-color="#D1C3F6" stop-opacity="0"/>
    </radialGradient>
    <linearGradient id="cardGrad" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#FAF8ED" stop-opacity="0.16"/>
      <stop offset="100%" stop-color="#FAF8ED" stop-opacity="0.05"/>
    </linearGradient>
  </defs>
  <rect width="1440" height="540" fill="url(#bg)"/>
  <rect width="1440" height="540" fill="url(#glow1)"/>
  <rect width="1440" height="540" fill="url(#glow2)"/>
  <g opacity="0.12" stroke="#FAF8ED" stroke-width="1">
    <line x1="0" y1="108" x2="1440" y2="108"/>
    <line x1="0" y1="216" x2="1440" y2="216"/>
    <line x1="0" y1="324" x2="1440" y2="324"/>
    <line x1="0" y1="432" x2="1440" y2="432"/>
    <line x1="288" y1="0" x2="288" y2="540"/>
    <line x1="576" y1="0" x2="576" y2="540"/>
    <line x1="864" y1="0" x2="864" y2="540"/>
    <line x1="1152" y1="0" x2="1152" y2="540"/>
  </g>
  <!-- Decorative Botanical Shapes & Showcase -->
  <circle cx="1110" cy="260" r="195" fill="#D7F0B4" fill-opacity="0.1" stroke="#D7F0B4" stroke-opacity="0.28" stroke-width="2" stroke-dasharray="8 8"/>
  <circle cx="1110" cy="260" r="142" fill="#D1C3F6" fill-opacity="0.12"/>
  <!-- Floating Product Cards Illustration -->
  <g transform="translate(860, 78)">
    <rect x="0" y="36" width="230" height="280" rx="28" fill="#FAF7EE" stroke="#253017" stroke-width="3"/>
    <rect x="22" y="60" width="94" height="26" rx="13" fill="#D7F0B4"/>
    <text x="69" y="77" text-anchor="middle" fill="#253017" font-family="sans-serif" font-size="11" font-weight="800">FRESCURA 100%</text>
    <circle cx="115" cy="158" r="52" fill="#EDF7DD" stroke="#98C85A" stroke-width="2"/>
    <path d="M98 175 C102 142, 130 130, 140 118 C142 148, 126 172, 98 175 Z" fill="#68A328"/>
    <path d="M95 162 C80 144, 86 122, 106 128 C112 142, 106 156, 95 162 Z" fill="#98C85A"/>
    <text x="115" y="242" text-anchor="middle" fill="#253017" font-family="Georgia, serif" font-size="20" font-weight="700">Canasta del Día</text>
    <text x="115" y="266" text-anchor="middle" fill="#5A5344" font-family="sans-serif" font-size="13" font-weight="600">Abarrotes · Bebidas · Hogar</text>
    <rect x="40" y="280" width="150" height="22" rx="11" fill="#253017"/>
    <text x="115" y="295" text-anchor="middle" fill="#D7F0B4" font-family="monospace" font-size="11" font-weight="700">OFERTAS ACTIVAS</text>
  </g>
  <g transform="translate(1118, 130)">
    <rect x="0" y="0" width="220" height="240" rx="26" fill="#EFEAFE" stroke="#253017" stroke-width="3"/>
    <rect x="22" y="24" width="110" height="24" rx="12" fill="#3C2B70"/>
    <text x="77" y="40" text-anchor="middle" fill="#FAF8ED" font-family="sans-serif" font-size="10" font-weight="800">DELIVERY BARRIO</text>
    <circle cx="110" cy="116" r="42" fill="#D1C3F6"/>
    <path d="M92 116 L106 130 L132 102" stroke="#253017" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
    <text x="110" y="188" text-anchor="middle" fill="#253017" font-family="Georgia, serif" font-size="18" font-weight="700">Atención Rápida</text>
    <text x="110" y="210" text-anchor="middle" fill="#4B3881" font-family="sans-serif" font-size="12" font-weight="700">Pedidos por WhatsApp</text>
  </g>
  <!-- Left Ambient Visual Accent -->
  <g transform="translate(88, 72)">
    <rect x="0" y="0" width="680" height="396" rx="34" fill="url(#cardGrad)" stroke="#D7F0B4" stroke-opacity="0.22" stroke-width="1.5"/>
  </g>
</svg>
SVG;

            $media = SiteMedia::query()->create([
                'section' => 'promo',
                'badge' => 'ESPACIO PUBLICITARIO · PRESENTACIÓN',
                'title' => 'Bienvenido a Bodega Los Olivos: Ofertas y Frescura Diaria',
                'subtitle' => 'Personaliza este espacio desde el Panel Admin con tus propias imágenes o videos promocionales (subida local guardada en base de datos o mediante URL web).',
                'cta_label' => 'Ver productos en oferta',
                'cta_url' => '#productos',
                'media_type' => 'image',
                'source_type' => 'upload',
                'mime_type' => 'image/svg+xml',
                'file_name' => 'presentacion-bodega-los-olivos.svg',
                'fit_mode' => 'cover',
                'show_overlay' => true,
                'active' => true,
                'sort_order' => 1,
            ]);

            $media->storeBinary($defaultSvg, 'image/svg+xml', 'presentacion-bodega-los-olivos.svg');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_media_chunks');
        Schema::dropIfExists('site_media');
    }
};
