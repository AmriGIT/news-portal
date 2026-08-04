<?php

namespace App\Services;

use App\Enums\NewsImportStatus;
use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\ImportToken;
use App\Models\NewsImport;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class NewsImportService
{
    public function __construct(
        private readonly ContentSanitizer $sanitizer,
        private readonly NewsImportTokenService $tokens,
        private readonly NewsImportZipService $zip,
        private readonly PostImageService $images,
    ) {}

    /**
     * @return array{payload: array<string, mixed>, status_code: int}
     */
    public function import(string $zipPath, string $originalFilename, string $publishMode, ImportToken $token, ?string $idempotencyKey, ?string $ipAddress, ?string $userAgent): array
    {
        if ($idempotencyKey && ($existing = NewsImport::query()->where('idempotency_key', $idempotencyKey)->first())) {
            File::delete($zipPath);

            return [
                'payload' => $existing->response_payload ?: $this->responsePayload($existing),
                'status_code' => $existing->failed_items > 0 ? 207 : 200,
            ];
        }

        $this->assertPublishModeAllowed($publishMode, $token);

        $newsImport = NewsImport::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $token->user_id,
            'import_token_id' => $token->id,
            'original_filename' => $originalFilename,
            'package_hash' => hash_file('sha256', $zipPath) ?: null,
            'idempotency_key' => $idempotencyKey,
            'requested_publish_mode' => $publishMode,
            'status' => NewsImportStatus::Uploaded,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'started_at' => now(),
        ]);

        $extractPath = null;

        try {
            $newsImport->update(['status' => NewsImportStatus::Validating]);
            $package = $this->zip->extractAndValidate($zipPath);
            $extractPath = $package['extract_path'];
            $manifest = $package['manifest'];
            $posts = $package['posts'];
            $sources = $package['sources'];
            $warnings = $package['warnings'];

            $newsImport->update([
                'status' => NewsImportStatus::Importing,
                'content_mode' => $manifest['content_mode'] ?? null,
                'image_mode' => $manifest['image_mode'] ?? null,
                'total_items' => count($posts),
                'warnings' => $warnings,
            ]);

            $sourceMap = $this->storeSources($newsImport, $sources);
            $postSourceMap = $this->postSourceMap($sources);
            $importedPosts = [];
            $errors = [];

            foreach ($posts as $index => $postPayload) {
                $row = $index + 1;

                if (! is_array($postPayload)) {
                    $errors[] = ['row' => $row, 'message' => 'Data artikel harus berupa object.'];
                    $newsImport->items()->create([
                        'title' => null,
                        'slug' => null,
                        'requested_status' => $publishMode,
                        'validation_errors' => ['Data artikel harus berupa object.'],
                    ]);

                    continue;
                }

                try {
                    $post = DB::transaction(fn (): Post => $this->importPost($newsImport, $postPayload, $publishMode, $extractPath, $postSourceMap, $sourceMap));
                    $importedPosts[] = [
                        'title' => $post->title,
                        'slug' => $post->slug,
                        'status' => $post->status->value,
                    ];
                } catch (Throwable $exception) {
                    $errors[] = [
                        'row' => $row,
                        'slug' => $postPayload['slug'] ?? null,
                        'message' => $exception->getMessage(),
                    ];

                    $newsImport->items()->create([
                        'title' => $postPayload['title'] ?? null,
                        'slug' => $postPayload['slug'] ?? null,
                        'requested_status' => $publishMode,
                        'validation_errors' => [$exception->getMessage()],
                    ]);
                }
            }

            $failed = count($errors);
            $imported = count($importedPosts);
            $status = $failed > 0 ? NewsImportStatus::CompletedWithErrors : NewsImportStatus::Completed;

            $newsImport->update([
                'status' => $status,
                'valid_items' => $imported,
                'invalid_items' => $failed,
                'imported_items' => $imported,
                'failed_items' => $failed,
                'completed_at' => now(),
            ]);

            $payload = $this->responsePayload($newsImport->fresh(), $importedPosts, $errors);
            $newsImport->update(['response_payload' => $payload]);
            $this->tokens->markUsed($token);

            return [
                'payload' => $payload,
                'status_code' => $failed > 0 ? 207 : 200,
            ];
        } catch (Throwable $exception) {
            $newsImport->update([
                'status' => NewsImportStatus::Failed,
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            $payload = [
                'success' => false,
                'message' => $exception->getMessage(),
                'import_id' => $newsImport->uuid,
                'status' => NewsImportStatus::Failed->value,
            ];

            $newsImport->update(['response_payload' => $payload]);

            return [
                'payload' => $payload,
                'status_code' => 422,
            ];
        } finally {
            if ($extractPath) {
                File::deleteDirectory($extractPath);
            }

            File::delete($zipPath);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, array<int, string>>  $postSourceMap
     * @param  array<string, int>  $sourceMap
     */
    private function importPost(NewsImport $newsImport, array $payload, string $publishMode, string $extractPath, array $postSourceMap, array $sourceMap): Post
    {
        $this->validatePayload($payload);

        $title = trim((string) $payload['title']);
        $slug = $this->uniqueSlug($payload['slug'] ?? $title);
        $category = $this->category($payload['category'] ?? null);
        $tagIds = $this->tagIds($payload['tags'] ?? []);
        $content = $this->content($payload['content'], $extractPath);
        $featuredImage = $this->featuredImage($payload['featured_image'] ?? null, $extractPath);
        $detailImages = $this->detailImages($payload['detail_images'] ?? [], $extractPath);
        $sourceIds = $postSourceMap[(string) ($payload['slug'] ?? $slug)] ?? [];
        $finalStatus = $publishMode === 'published' ? PostStatus::Published : PostStatus::Draft;
        $authorId = $this->authorId($newsImport);

        $post = Post::query()->create([
            'author_id' => $authorId,
            'editor_id' => null,
            'category_id' => $category->id,
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $this->nullableString($payload['excerpt'] ?? null),
            'content' => $content,
            'featured_image' => $featuredImage,
            'featured_image_alt' => $this->altText($payload['featured_image_alt'] ?? null, $title),
            'featured_image_caption' => $this->nullableString($payload['featured_image_caption'] ?? null),
            'featured_image_credit' => $this->nullableString($payload['featured_image_credit'] ?? 'BebasInfo'),
            'detail_images' => $detailImages,
            'status' => $finalStatus,
            'is_featured' => (bool) ($payload['is_featured'] ?? false),
            'published_at' => $finalStatus === PostStatus::Published ? now() : null,
            'seo_title' => $this->nullableString($payload['seo_title'] ?? null),
            'seo_description' => $this->nullableString($payload['seo_description'] ?? null),
            'canonical_url' => null,
            'og_image' => null,
            'robots_index' => $this->boolean($payload['robots_index'] ?? true),
            'robots_follow' => $this->boolean($payload['robots_follow'] ?? true),
        ]);

        if ($tagIds !== []) {
            $post->tags()->sync($tagIds);
        }

        $newsImport->items()->create([
            'post_id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'requested_status' => $publishMode,
            'final_status' => $post->status->value,
            'source_ids' => $sourceIds,
            'image_path' => $post->featured_image,
            'warnings' => $featuredImage === null ? ['Featured image kosong, frontend memakai default image.'] : [],
        ]);

        return $post;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validatePayload(array $payload): void
    {
        $title = trim((string) ($payload['title'] ?? ''));
        $content = trim((string) ($payload['content'] ?? ''));

        if ($title === '') {
            throw new RuntimeException('Judul wajib diisi.');
        }

        if (mb_strlen($title) > 255) {
            throw new RuntimeException('Judul maksimal 255 karakter.');
        }

        if ($content === '' || trim(strip_tags($content)) === '') {
            throw new RuntimeException('Konten wajib diisi.');
        }

        if (preg_match('/<\s*h1\b/i', $content) === 1) {
            throw new RuntimeException('Konten tidak boleh mengandung H1.');
        }

        if (filled($payload['seo_title'] ?? null) && mb_strlen((string) $payload['seo_title']) > 80) {
            throw new RuntimeException('SEO title terlalu panjang.');
        }

        if (filled($payload['seo_description'] ?? null) && mb_strlen((string) $payload['seo_description']) > 180) {
            throw new RuntimeException('SEO description terlalu panjang.');
        }

        if (isset($payload['tags']) && ! is_array($payload['tags'])) {
            throw new RuntimeException('Tags harus berupa array.');
        }

        if (isset($payload['detail_images']) && ! is_array($payload['detail_images'])) {
            throw new RuntimeException('Detail images harus berupa array.');
        }
    }

    private function assertPublishModeAllowed(string $publishMode, ImportToken $token): void
    {
        if ($publishMode !== 'published') {
            return;
        }

        if (! config('news-import.allow_publish', true) || ! $token->can('news:publish')) {
            throw new RuntimeException('Token tidak memiliki izin untuk mempublikasikan artikel.');
        }
    }

    private function category(mixed $value): Category
    {
        $name = trim((string) $value);

        if ($name === '') {
            throw new RuntimeException('Kategori wajib diisi.');
        }

        $slug = Str::slug($name);

        $category = Category::query()
            ->where('slug', $slug)
            ->orWhere('name', $name)
            ->first();

        if (! $category) {
            throw new RuntimeException("Kategori {$name} tidak ditemukan.");
        }

        return $category;
    }

    /**
     * @return array<int, int>
     */
    private function tagIds(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return collect($values)
            ->map(fn (mixed $tag): string => trim((string) $tag))
            ->filter()
            ->unique(fn (string $tag): string => Str::slug($tag))
            ->take(6)
            ->map(function (string $name): int {
                return Tag::query()->firstOrCreate([
                    'slug' => Str::slug($name),
                ], [
                    'name' => $name,
                ])->id;
            })
            ->values()
            ->all();
    }

    private function content(mixed $content, string $extractPath): string
    {
        $html = $this->importContentImages((string) $content, $extractPath);

        return $this->sanitizer->sanitize($html);
    }

    private function featuredImage(mixed $path, string $extractPath): ?string
    {
        if (blank($path)) {
            return $this->defaultFeaturedImage();
        }

        try {
            $imagePath = $this->resolveImportFile((string) $path, $extractPath);
        } catch (RuntimeException $exception) {
            if ($this->isMissingImageException($exception)) {
                return $this->defaultFeaturedImage();
            }

            throw $exception;
        }

        $file = new UploadedFile($imagePath, basename($imagePath), null, null, true);

        return $this->images->storeFeaturedImage($file)['original'];
    }

    /**
     * @return array<int, string>
     */
    private function detailImages(mixed $paths, string $extractPath): array
    {
        if (! is_array($paths)) {
            return [];
        }

        return collect($paths)
            ->map(fn (mixed $item): ?string => is_array($item) ? ($item['path'] ?? null) : (is_string($item) ? $item : null))
            ->filter(fn (?string $path): bool => filled($path))
            ->map(function (string $path) use ($extractPath): ?string {
                try {
                    $imagePath = $this->resolveImportFile($path, $extractPath);
                } catch (RuntimeException $exception) {
                    if ($this->isMissingImageException($exception)) {
                        return null;
                    }

                    throw $exception;
                }

                $file = new UploadedFile($imagePath, basename($imagePath), null, null, true);

                return $this->images->storeFeaturedImage($file)['original'];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function importContentImages(string $content, string $extractPath): string
    {
        if (! str_contains($content, '<img')) {
            return $content;
        }

        $document = new DOMDocument;
        $document->loadHTML('<!DOCTYPE html><html><body>'.$content.'</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);

        foreach ($xpath->query('//img') ?: [] as $image) {
            if (! $image instanceof DOMElement) {
                continue;
            }

            $src = trim($image->getAttribute('src'));

            if ($src === '' || preg_match('#^(https?:)?//#i', $src) === 1 || str_starts_with($src, 'data:') || str_starts_with($src, '/')) {
                continue;
            }

            $imagePath = $this->resolveImportFile($src, $extractPath);
            $file = new UploadedFile($imagePath, basename($imagePath), null, null, true);
            $storedPath = $this->images->storeContentImage($file);
            $image->setAttribute('src', Storage::disk(config('media.disk', 'public'))->url($storedPath));
        }

        $body = $document->getElementsByTagName('body')->item(0);
        $output = '';

        foreach ($body?->childNodes ?? [] as $childNode) {
            $output .= $document->saveHTML($childNode);
        }

        return trim($output);
    }

    private function resolveImportFile(string $path, string $extractPath): string
    {
        $path = trim(str_replace('\\', '/', $path));
        $segments = array_filter(explode('/', $path), fn (string $segment): bool => $segment !== '');

        if ($path === '' || in_array('..', $segments, true) || str_starts_with($path, '/') || preg_match('/^[a-zA-Z]:\//', $path) === 1) {
            throw new RuntimeException("Path gambar {$path} tidak valid.");
        }

        $realBase = realpath($extractPath);
        $realPath = realpath($extractPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path));

        if ($realBase === false || $realPath === false || ! str_starts_with($realPath, $realBase.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException("Gambar {$path} tidak ditemukan.");
        }

        return $realPath;
    }

    private function defaultFeaturedImage(): ?string
    {
        if (config('news-import.allow_default_image', true)) {
            return null;
        }

        throw new RuntimeException('Featured image wajib tersedia.');
    }

    private function isMissingImageException(RuntimeException $exception): bool
    {
        return str_contains($exception->getMessage(), 'tidak ditemukan');
    }

    private function authorId(NewsImport $newsImport): int
    {
        $authorId = $newsImport->user_id ?: $newsImport->importToken?->user_id;

        if ($authorId) {
            return $authorId;
        }

        $fallbackId = User::query()
            ->where('is_active', true)
            ->oldest('id')
            ->value('id');

        if (! $fallbackId) {
            throw new RuntimeException('Author import tidak tersedia.');
        }

        return (int) $fallbackId;
    }

    /**
     * @param  array<string, mixed>  $sources
     * @return array<string, int>
     */
    private function storeSources(NewsImport $newsImport, array $sources): array
    {
        return collect($sources['sources'] ?? [])
            ->filter(fn (mixed $source): bool => is_array($source) && filled($source['id'] ?? null))
            ->mapWithKeys(function (array $source) use ($newsImport): array {
                $model = $newsImport->sources()->create([
                    'source_id' => (string) $source['id'],
                    'requested_url' => $this->nullableString($source['requested_url'] ?? null),
                    'final_url' => $this->nullableString($source['final_url'] ?? null),
                    'publisher' => $this->nullableString($source['publisher'] ?? null),
                    'title' => $this->nullableString($source['title'] ?? null),
                    'author' => $this->nullableString($source['author'] ?? null),
                    'published_at' => $this->date($source['published_at'] ?? null),
                    'retrieved_at' => $this->date($source['retrieved_at'] ?? null),
                    'sha256' => $this->nullableString($source['sha256'] ?? null),
                ]);

                return [$model->source_id => $model->id];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $sources
     * @return array<string, array<int, string>>
     */
    private function postSourceMap(array $sources): array
    {
        return collect($sources['post_sources'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item) && filled($item['slug'] ?? null))
            ->mapWithKeys(fn (array $item): array => [(string) $item['slug'] => array_values(array_filter((array) ($item['source_ids'] ?? [])))])
            ->all();
    }

    private function uniqueSlug(mixed $value): string
    {
        $baseSlug = Str::slug((string) $value) ?: Str::random(8);
        $slug = $baseSlug;
        $suffix = 2;

        while (Post::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function altText(mixed $value, string $title): string
    {
        $altText = $this->nullableString($value)
            ?: $this->nullableString($title)
            ?: config('media.featured.default_alt', 'Gambar berita');

        return (string) $altText;
    }

    private function boolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
    }

    private function date(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $posts
     * @param  array<int, array<string, mixed>>  $errors
     * @return array<string, mixed>
     */
    private function responsePayload(NewsImport $newsImport, array $posts = [], array $errors = []): array
    {
        $failed = $newsImport->failed_items;
        $success = $newsImport->status !== NewsImportStatus::Failed;

        return array_filter([
            'success' => $success,
            'message' => $failed > 0 ? 'Import selesai dengan beberapa kegagalan.' : 'Import berita berhasil.',
            'import_id' => $newsImport->uuid,
            'status' => $newsImport->status->value,
            'requested_publish_mode' => $newsImport->requested_publish_mode,
            'total' => $newsImport->total_items,
            'imported' => $newsImport->imported_items,
            'failed' => $newsImport->failed_items,
            'posts' => $posts,
            'warnings' => $newsImport->warnings ?: [],
            'errors' => $errors,
        ], fn (mixed $value): bool => $value !== null);
    }
}
