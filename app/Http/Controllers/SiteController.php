<?php

namespace App\Http\Controllers;

use App\Models\ContactSubmission;
use App\Models\Product;
use App\Models\SiteMedia;
use App\Models\SiteSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SiteController extends Controller
{
    private const DEFAULTS = [
        'promo' => ['title' => 'Promociones y presentación de temporada', 'content' => 'Descubre nuestras ofertas del día, productos frescos y novedades pensadas para tu familia.', 'cta_label' => 'Ver catálogo de ofertas', 'cta_url' => '#productos', 'sort_order' => 0],
        'home' => ['title' => 'Lo que buscas, siempre cerca.', 'content' => 'Tu bodega de confianza: productos de calidad, buenos precios y atención cercana.', 'cta_label' => 'Escríbenos por WhatsApp', 'sort_order' => 1],
        'banner' => ['title' => 'Frescura y buenos precios todos los días', 'content' => 'Abarrotes, bebidas y productos esenciales para tu hogar.', 'cta_label' => 'Ver productos', 'sort_order' => 2],
        'about' => ['title' => 'Cerca de ti, como siempre', 'content' => 'Somos una bodega de barrio que te atiende con una sonrisa y lo que necesitas para cada día.', 'cta_label' => null, 'sort_order' => 3],
        'contact' => ['title' => '¿Qué necesitas hoy?', 'content' => 'Déjanos tu mensaje. Te responderemos por WhatsApp.', 'cta_label' => 'Enviar consulta', 'sort_order' => 4],
        'faq' => ['title' => 'Preguntas frecuentes', 'content' => "¿Dónde estamos? En tu barrio, siempre cerca.\n¿Cuáles son sus horarios? Atendemos todos los días.\n¿Puedo consultar disponibilidad? Sí, escríbenos por WhatsApp.", 'cta_label' => null, 'sort_order' => 5],
    ];

    private function sections()
    {
        $saved = SiteSection::query()->get()->keyBy('section');

        return collect(self::DEFAULTS)->map(fn (array $defaults, string $key) => $saved->get($key) ?? new SiteSection($defaults + ['section' => $key, 'active' => true]));
    }

    public function home(): View
    {
        $products = Product::query()
            ->where('active', true)
            ->where('is_public', true)
            ->where('stock', '>', 0)
            ->orderBy('category')
            ->orderBy('name')
            ->limit(16)
            ->get();

        $promoMedia = SiteMedia::query()
            ->withCount('chunks')
            ->where('section', 'promo')
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('site.home', [
            'sections' => $this->sections(),
            'promoMedia' => $promoMedia,
            'products' => $products,
            'categories' => $products->pluck('category')->filter()->unique()->values(),
        ]);
    }

    public function editor(): View
    {
        $promoMedia = SiteMedia::query()
            ->withCount('chunks')
            ->where('section', 'promo')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.site.edit', [
            'sections' => $this->sections(),
            'promoMedia' => $promoMedia,
        ]);
    }

    /**
     * Serve image or video binary directly from the database (with HTTP 206 Range support for video streaming).
     */
    public function media(Request $request, SiteMedia $media): Response
    {
        $binary = $media->decodedBinary();

        if ($binary === null) {
            if (! empty($media->external_url)) {
                return redirect()->away($media->external_url);
            }
            abort(404);
        }

        $size = strlen($binary);
        $mime = $media->mime_type ?: 'application/octet-stream';
        $etag = '"'.md5($media->id.'-'.($media->updated_at?->timestamp ?? 0).'-'.$size).'"';

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304, [
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        }

        $headers = [
            'Content-Type' => $mime,
            'Accept-Ranges' => 'bytes',
            'ETag' => $etag,
            'Cache-Control' => 'public, max-age=31536000',
        ];

        $range = $request->header('Range');
        if ($range && preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
            $start = (int) $matches[1];
            $end = $matches[2] !== '' ? (int) $matches[2] : ($size - 1);
            $end = min($end, $size - 1);

            if ($start <= $end && $start < $size) {
                $length = $end - $start + 1;
                $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
                $headers['Content-Length'] = (string) $length;

                return response(substr($binary, $start, $length), 206, $headers);
            }
        }

        $headers['Content-Length'] = (string) $size;

        return response($binary, 200, $headers);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sections' => ['required', 'array'],
            'sections.*.title' => ['required', 'string', 'max:160'],
            'sections.*.content' => ['nullable', 'string', 'max:4000'],
            'sections.*.cta_label' => ['nullable', 'string', 'max:80'],
            'sections.*.cta_url' => ['nullable', 'string', 'max:255'],
            'sections.*.whatsapp' => ['nullable', 'string', 'max:32'],
            'sections.*.active' => ['nullable', 'boolean'],

            'media_items' => ['nullable', 'array'],
            'media_items.*.title' => ['nullable', 'string', 'max:160'],
            'media_items.*.subtitle' => ['nullable', 'string', 'max:1000'],
            'media_items.*.badge' => ['nullable', 'string', 'max:80'],
            'media_items.*.cta_label' => ['nullable', 'string', 'max:80'],
            'media_items.*.cta_url' => ['nullable', 'string', 'max:255'],
            'media_items.*.media_type' => ['nullable', 'in:auto,image,video'],
            'media_items.*.source_type' => ['nullable', 'in:upload,url'],
            'media_items.*.external_url' => ['nullable', 'url', 'max:2048'],
            'media_items.*.fit_mode' => ['nullable', 'in:cover,contain,auto'],
            'media_items.*.show_overlay' => ['nullable', 'boolean'],
            'media_items.*.active' => ['nullable', 'boolean'],
            'media_items.*.delete' => ['nullable', 'boolean'],
            'media_items.*.file' => ['nullable', 'file', 'max:38912'],

            'new_media' => ['nullable', 'array'],
            'new_media.action_mode' => ['nullable', 'in:append,replace_first'],
            'new_media.title' => ['nullable', 'string', 'max:160'],
            'new_media.subtitle' => ['nullable', 'string', 'max:1000'],
            'new_media.badge' => ['nullable', 'string', 'max:80'],
            'new_media.cta_label' => ['nullable', 'string', 'max:80'],
            'new_media.cta_url' => ['nullable', 'string', 'max:255'],
            'new_media.media_type' => ['nullable', 'in:auto,image,video'],
            'new_media.source_type' => ['nullable', 'in:upload,url'],
            'new_media.external_url' => ['nullable', 'url', 'max:2048'],
            'new_media.fit_mode' => ['nullable', 'in:cover,contain,auto'],
            'new_media.show_overlay' => ['nullable', 'boolean'],
            'new_media.file' => ['nullable', 'file', 'max:38912'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            // 1. Update text sections
            foreach ($data['sections'] as $key => $section) {
                Validator::make(['section' => $key], ['section' => ['required', 'in:promo,home,banner,about,contact,faq']])->validate();
                SiteSection::query()->updateOrCreate(
                    ['section' => $key],
                    [...$section, 'active' => (bool) ($section['active'] ?? false)]
                );
            }

            // 2. Update existing media items
            if (! empty($data['media_items']) && is_array($data['media_items'])) {
                foreach ($data['media_items'] as $id => $itemData) {
                    $media = SiteMedia::query()->find($id);
                    if (! $media) {
                        continue;
                    }

                    if (! empty($itemData['delete'])) {
                        $media->delete();
                        continue;
                    }

                    $uploadedFile = $request->file("media_items.{$id}.file");
                    $externalUrl = trim((string) ($itemData['external_url'] ?? ''));
                    $requestedSource = $itemData['source_type'] ?? $media->source_type;
                    $requestedMediaType = $itemData['media_type'] ?? 'auto';

                    $media->fill([
                        'title' => $itemData['title'] ?? null,
                        'subtitle' => $itemData['subtitle'] ?? null,
                        'badge' => $itemData['badge'] ?? null,
                        'cta_label' => $itemData['cta_label'] ?? null,
                        'cta_url' => $itemData['cta_url'] ?? null,
                        'fit_mode' => $itemData['fit_mode'] ?? 'cover',
                        'show_overlay' => (bool) ($itemData['show_overlay'] ?? false),
                        'active' => (bool) ($itemData['active'] ?? false),
                    ]);

                    if ($uploadedFile instanceof UploadedFile && $uploadedFile->isValid()) {
                        $this->applyUploadedFileToMedia($media, $uploadedFile, $requestedMediaType);
                    } elseif ($requestedSource === 'url' && $externalUrl !== '') {
                        $urlChanged = $externalUrl !== (string) $media->external_url || $media->source_type !== 'url';
                        $this->applyExternalUrlToMedia($media, $externalUrl, $requestedMediaType, $urlChanged);
                    } elseif ($requestedMediaType !== 'auto') {
                        $media->media_type = $requestedMediaType;
                        $media->save();
                    } else {
                        $media->save();
                    }
                }
            }

            // 3. Process new_media (if user uploaded a new file or entered a new URL in the "Agregar / Cambiar" box)
            $newFile = $request->file('new_media.file');
            $newUrl = trim((string) ($data['new_media']['external_url'] ?? ''));
            $hasNewFile = $newFile instanceof UploadedFile && $newFile->isValid();
            $hasNewUrl = $newUrl !== '';

            if ($hasNewFile || $hasNewUrl) {
                $newMeta = $data['new_media'] ?? [];
                $actionMode = $newMeta['action_mode'] ?? 'append';
                $requestedMediaType = $newMeta['media_type'] ?? 'auto';

                $targetMedia = null;
                if ($actionMode === 'replace_first') {
                    $targetMedia = SiteMedia::query()
                        ->where('section', 'promo')
                        ->orderBy('sort_order')
                        ->orderBy('id')
                        ->first();
                }

                if (! $targetMedia) {
                    $maxSort = (int) SiteMedia::query()->where('section', 'promo')->max('sort_order');
                    $targetMedia = new SiteMedia([
                        'section' => 'promo',
                        'sort_order' => $maxSort + 1,
                        'active' => true,
                    ]);
                }

                $targetMedia->fill([
                    'title' => $newMeta['title'] ?? $targetMedia->title,
                    'subtitle' => $newMeta['subtitle'] ?? $targetMedia->subtitle,
                    'badge' => $newMeta['badge'] ?? ($targetMedia->badge ?: 'PUBLICIDAD · DESTACADO'),
                    'cta_label' => $newMeta['cta_label'] ?? $targetMedia->cta_label,
                    'cta_url' => $newMeta['cta_url'] ?? $targetMedia->cta_url,
                    'fit_mode' => $newMeta['fit_mode'] ?? ($targetMedia->fit_mode ?: 'cover'),
                    'show_overlay' => array_key_exists('show_overlay', $newMeta) ? (bool) $newMeta['show_overlay'] : true,
                    'active' => true,
                ]);
                $targetMedia->save();

                if ($hasNewFile) {
                    $this->applyUploadedFileToMedia($targetMedia, $newFile, $requestedMediaType);
                } else {
                    $this->applyExternalUrlToMedia($targetMedia, $newUrl, $requestedMediaType, true);
                }
            }
        });

        return back()->with('success', 'La página pública y el contenido multimedia quedaron guardados en la base de datos.');
    }

    public function destroyMedia(SiteMedia $media): RedirectResponse
    {
        $media->delete();

        return back()->with('success', 'El elemento multimedia fue eliminado.');
    }

    private function applyUploadedFileToMedia(SiteMedia $media, UploadedFile $file, string $requestedMediaType): void
    {
        $mime = $file->getMimeType() ?: ($file->getClientMimeType() ?: 'application/octet-stream');
        $ext = strtolower($file->getClientOriginalExtension());
        $isVideo = str_starts_with($mime, 'video/') || in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'm4v'], true);

        $mediaType = $requestedMediaType !== 'auto'
            ? $requestedMediaType
            : ($isVideo ? 'video' : 'image');

        $media->forceFill([
            'source_type' => 'upload',
            'media_type' => $mediaType,
            'external_url' => null,
        ])->save();

        $binary = (string) file_get_contents($file->getRealPath());
        $media->storeBinary($binary, $mime, $file->getClientOriginalName());
    }

    private function applyExternalUrlToMedia(SiteMedia $media, string $url, string $requestedMediaType, bool $syncRemoteImage): void
    {
        $isEmbedVideo = (bool) preg_match('~(?:youtube\.com/|youtu\.be/|vimeo\.com/)~i', $url);
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));
        $isDirectVideo = (bool) preg_match('~\.(mp4|webm|ogg|mov|m4v)$~i', $path);

        $mediaType = $requestedMediaType !== 'auto'
            ? $requestedMediaType
            : (($isEmbedVideo || $isDirectVideo) ? 'video' : 'image');

        $media->forceFill([
            'source_type' => 'url',
            'media_type' => $mediaType,
            'external_url' => $url,
        ])->save();

        // If it's a web image URL, also attempt to download and store a copy in the database
        // so that opening the project on any new laptop or offline still loads the image from the DB!
        if ($syncRemoteImage && $mediaType === 'image' && ! $isEmbedVideo) {
            try {
                $response = Http::timeout(6)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'])
                    ->get($url);

                if ($response->successful()) {
                    $contentType = strtolower((string) $response->header('Content-Type'));
                    $body = $response->body();
                    if (str_starts_with($contentType, 'image/') && strlen($body) > 0 && strlen($body) <= 25 * 1024 * 1024) {
                        $cleanMime = trim(explode(';', $contentType)[0]);
                        $fileName = basename((string) parse_url($url, PHP_URL_PATH)) ?: 'imagen-web.jpg';
                        $media->storeBinary($body, $cleanMime, $fileName);
                    }
                }
            } catch (\Throwable) {
                // Keep external_url fallback if remote host is unreachable
            }
        } elseif ($mediaType === 'video') {
            $media->chunks()->delete();
            $media->forceFill(['file_size' => null, 'file_name' => null, 'mime_type' => null])->save();
        }
    }

    public function contact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);
        ContactSubmission::query()->create($data);
        $whatsapp = SiteSection::query()->where('section', 'contact')->value('whatsapp');

        if ($whatsapp) {
            $text = "Hola, soy {$data['name']}. {$data['message']}";

            return redirect()->away('https://wa.me/'.preg_replace('/\D+/', '', $whatsapp).'?text='.urlencode($text));
        }

        return redirect()->route('home')->with('contact_sent', 'Recibimos tu consulta. Pronto nos pondremos en contacto contigo.');
    }
}
