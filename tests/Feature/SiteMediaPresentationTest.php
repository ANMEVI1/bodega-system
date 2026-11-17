<?php

namespace Tests\Feature;

use App\Models\SiteMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SiteMediaPresentationTest extends TestCase
{
    use RefreshDatabase;

    private function baseSectionsPayload(): array
    {
        return [
            'promo' => ['title' => 'Promociones', 'content' => 'Ofertas del día', 'active' => '1'],
            'home' => ['title' => 'Lo que buscas, siempre cerca.', 'content' => 'Tu bodega de confianza', 'cta_label' => 'WhatsApp', 'cta_url' => '#productos', 'active' => '1'],
            'banner' => ['title' => 'Frescura y buenos precios', 'content' => 'Abarrotes y bebidas', 'cta_label' => 'Ver', 'cta_url' => '#productos', 'active' => '1'],
            'about' => ['title' => 'Cerca de ti', 'content' => 'Somos una bodega de barrio', 'active' => '1'],
            'contact' => ['title' => '¿Qué necesitas hoy?', 'content' => 'Déjanos tu mensaje', 'cta_label' => 'Enviar', 'whatsapp' => '51987654321', 'active' => '1'],
            'faq' => ['title' => 'Preguntas frecuentes', 'content' => '¿Dónde estamos? En tu barrio.', 'active' => '1'],
        ];
    }

    public function test_default_promo_media_is_stored_in_database_and_renders_on_home_and_admin(): void
    {
        $media = SiteMedia::query()->where('section', 'promo')->first();
        $this->assertNotNull($media);
        $this->assertTrue($media->hasDatabaseBinary());

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-promo-carousel', false)
            ->assertSee(route('site.media', ['media' => $media->id, 'v' => $media->updated_at?->timestamp ?: 1]), false);

        $this->get(route('site.media', $media))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.site.edit'))
            ->assertOk()
            ->assertSee('Publicidad, Banner o Presentación Multimedia (Imagen / Video)')
            ->assertSee('Subir archivo local')
            ->assertSee('Usar URL web');
    }

    public function test_local_image_and_video_uploads_are_saved_in_database_chunks_and_streamed(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $existing = SiteMedia::query()->where('section', 'promo')->firstOrFail();
        $fakePngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $imageFile = UploadedFile::fake()->createWithContent('publicidad-bodega.png', $fakePngBytes);

        $fakeMp4Bytes = str_repeat('VIDEODATA1234567890', 60);
        $videoFile = UploadedFile::fake()->createWithContent('presentacion.mp4', $fakeMp4Bytes);

        $this->put(route('admin.site.update'), [
            'sections' => $this->baseSectionsPayload(),
            'media_items' => [
                $existing->id => [
                    'title' => 'Super Oferta en Abarrotes',
                    'subtitle' => 'Descuentos especiales toda la semana',
                    'badge' => 'OFERTA LOCAL',
                    'cta_label' => 'Ver catálogo',
                    'cta_url' => '#productos',
                    'media_type' => 'auto',
                    'source_type' => 'upload',
                    'fit_mode' => 'contain',
                    'show_overlay' => '1',
                    'active' => '1',
                    'file' => $imageFile,
                ],
            ],
            'new_media' => [
                'action_mode' => 'append',
                'title' => 'Video de Presentación',
                'subtitle' => 'Conoce nuestra tienda por dentro',
                'badge' => 'PRESENTACIÓN',
                'media_type' => 'auto',
                'source_type' => 'upload',
                'fit_mode' => 'cover',
                'show_overlay' => '1',
                'file' => $videoFile,
            ],
        ])->assertRedirect();

        $existing->refresh();
        $this->assertSame('image', $existing->media_type);
        $this->assertSame('upload', $existing->source_type);
        $this->assertSame('contain', $existing->fit_mode);
        $this->assertSame($fakePngBytes, $existing->decodedBinary());

        $videoMedia = SiteMedia::query()->where('id', '!=', $existing->id)->firstOrFail();
        $this->assertSame('video', $videoMedia->media_type);
        $this->assertSame('upload', $videoMedia->source_type);
        $this->assertSame($fakeMp4Bytes, $videoMedia->decodedBinary());

        // Verify HTTP 206 Partial Content streaming for video from database
        $rangeResponse = $this->withHeaders(['Range' => 'bytes=0-19'])
            ->get(route('site.media', $videoMedia));

        $rangeResponse->assertStatus(206)
            ->assertHeader('Accept-Ranges', 'bytes');
        $this->assertSame(substr($fakeMp4Bytes, 0, 20), $rangeResponse->getContent());
    }

    public function test_web_url_supports_remote_image_database_backup_and_youtube_embeds(): void
    {
        $fakeRemoteImage = 'REMOTE_IMAGE_BINARY_CONTENT_FOR_DB_BACKUP';
        Http::fake([
            'https://cdn.example.com/banner-oferta.webp' => Http::response($fakeRemoteImage, 200, ['Content-Type' => 'image/webp']),
        ]);

        $this->actingAs(User::factory()->create());
        $existing = SiteMedia::query()->where('section', 'promo')->firstOrFail();

        $this->put(route('admin.site.update'), [
            'sections' => $this->baseSectionsPayload(),
            'media_items' => [
                $existing->id => [
                    'title' => 'Banner desde URL Web',
                    'subtitle' => 'Respaldado en BD automáticamente',
                    'badge' => 'WEB + BD',
                    'media_type' => 'auto',
                    'source_type' => 'url',
                    'external_url' => 'https://cdn.example.com/banner-oferta.webp',
                    'fit_mode' => 'cover',
                    'show_overlay' => '1',
                    'active' => '1',
                ],
            ],
            'new_media' => [
                'action_mode' => 'append',
                'title' => 'Spot en YouTube',
                'media_type' => 'auto',
                'source_type' => 'url',
                'external_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'fit_mode' => 'cover',
                'show_overlay' => '0',
            ],
        ])->assertRedirect();

        $existing->refresh();
        $this->assertSame('url', $existing->source_type);
        $this->assertSame('image', $existing->media_type);
        $this->assertTrue($existing->hasDatabaseBinary());
        $this->assertSame($fakeRemoteImage, $existing->decodedBinary());

        $ytMedia = SiteMedia::query()->where('id', '!=', $existing->id)->firstOrFail();
        $this->assertSame('video', $ytMedia->media_type);
        $this->assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ?rel=0&modestbranding=1', $ytMedia->embedUrl());

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('https://www.youtube.com/embed/dQw4w9WgXcQ?rel=0&modestbranding=1');
    }
}
