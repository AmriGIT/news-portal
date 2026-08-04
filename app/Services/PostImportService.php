<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Models\Category;
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
use JsonException;
use RuntimeException;
use Throwable;
use ZipArchive;

class PostImportService
{
    public function __construct(
        private readonly ContentSanitizer $sanitizer,
        private readonly PostImageService $images,
    ) {}

    /**
     * @return array{imported: int, failed: int, failures: array<int, string>}
     */
    public function importFromZip(string $zipPath, User $actor): array
    {
        if (! $actor->isAdmin()) {
            throw new RuntimeException('Import berita hanya dapat dilakukan admin.');
        }

        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi PHP Zip belum tersedia.');
        }

        if (! is_file($zipPath)) {
            throw new RuntimeException('File ZIP import tidak ditemukan.');
        }

        $extractPath = storage_path('app/private/imports/extracted/'.Str::uuid());
        File::ensureDirectoryExists($extractPath);

        try {
            $this->extractZip($zipPath, $extractPath);
            $records = $this->readManifest($extractPath);

            return $this->importRecords($records, $extractPath, $actor);
        } finally {
            File::deleteDirectory($extractPath);
        }
    }

    /**
     * @param  array<int, mixed>  $records
     * @return array{imported: int, failed: int, failures: array<int, string>}
     */
    private function importRecords(array $records, string $extractPath, User $actor): array
    {
        $imported = 0;
        $failures = [];

        foreach ($records as $index => $record) {
            $row = $index + 1;

            if (! is_array($record)) {
                $failures[] = "Baris {$row}: data post harus berupa object.";

                continue;
            }

            try {
                DB::transaction(function () use ($record, $extractPath, $actor): void {
                    $this->importRecord($record, $extractPath, $actor);
                });

                $imported++;
            } catch (Throwable $exception) {
                $failures[] = "Baris {$row}: ".$exception->getMessage();
            }
        }

        return [
            'imported' => $imported,
            'failed' => count($failures),
            'failures' => $failures,
        ];
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function importRecord(array $record, string $extractPath, User $actor): void
    {
        $title = trim((string) ($record['title'] ?? ''));
        $content = trim((string) ($record['content'] ?? ''));

        if ($title === '') {
            throw new RuntimeException('Judul berita wajib diisi.');
        }

        if (trim(strip_tags($content)) === '' && ! str_contains($content, '<img')) {
            throw new RuntimeException('Isi berita wajib diisi.');
        }

        $category = $this->category($record['category'] ?? null);
        $tagIds = $this->tagIds($record['tags'] ?? []);
        $status = $this->status($record['status'] ?? null);
        $publishedAt = $this->publishedAt($record['published_at'] ?? null, $status);
        $content = $this->importContentImages($content, $extractPath);
        $featuredImage = $this->featuredImage($record['featured_image'] ?? null, $extractPath);
        $detailImages = $this->detailImages($record['detail_images'] ?? [], $extractPath);

        $post = Post::query()->create([
            'author_id' => $this->userId($record['author_id'] ?? null) ?: $actor->id,
            'editor_id' => $this->userId($record['editor_id'] ?? null),
            'category_id' => $category->id,
            'title' => $title,
            'slug' => $this->uniquePostSlug($record['slug'] ?? $title),
            'excerpt' => $this->nullableString($record['excerpt'] ?? null),
            'content' => $this->sanitizer->sanitize($content),
            'featured_image' => $featuredImage,
            'featured_image_alt' => $this->nullableString($record['featured_image_alt'] ?? $title),
            'featured_image_caption' => $this->nullableString($record['featured_image_caption'] ?? null),
            'featured_image_credit' => $this->nullableString($record['featured_image_credit'] ?? null),
            'detail_images' => $detailImages,
            'status' => $status,
            'is_featured' => (bool) ($record['is_featured'] ?? false),
            'published_at' => $publishedAt,
            'seo_title' => $this->nullableString($record['seo_title'] ?? null),
            'seo_description' => $this->nullableString($record['seo_description'] ?? null),
            'canonical_url' => $this->nullableString($record['canonical_url'] ?? $record['source_url'] ?? null),
            'og_image' => $this->nullableString($record['og_image'] ?? null),
            'robots_index' => $this->boolean($record['robots_index'] ?? true),
            'robots_follow' => $this->boolean($record['robots_follow'] ?? true),
        ]);

        if ($tagIds !== []) {
            $post->tags()->sync($tagIds);
        }
    }

    private function extractZip(string $zipPath, string $extractPath): void
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('File ZIP tidak dapat dibuka.');
        }

        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = (string) $zip->getNameIndex($index);
                $relativePath = $this->safeRelativePath($name);

                if ($relativePath === null || str_ends_with($name, '/')) {
                    continue;
                }

                $stream = $zip->getStream($name);

                if ($stream === false) {
                    throw new RuntimeException("File {$name} di dalam ZIP tidak dapat dibaca.");
                }

                $targetPath = $extractPath.DIRECTORY_SEPARATOR.$relativePath;
                File::ensureDirectoryExists(dirname($targetPath));
                File::put($targetPath, stream_get_contents($stream));
                fclose($stream);
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array<int, mixed>
     */
    private function readManifest(string $extractPath): array
    {
        $manifestPath = $extractPath.DIRECTORY_SEPARATOR.'posts.json';

        if (! is_file($manifestPath)) {
            throw new RuntimeException('ZIP wajib berisi file posts.json di root.');
        }

        try {
            $data = json_decode((string) File::get($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('posts.json tidak valid: '.$exception->getMessage(), previous: $exception);
        }

        $records = is_array($data) && array_key_exists('posts', $data) ? $data['posts'] : $data;

        if (! is_array($records)) {
            throw new RuntimeException('posts.json harus berupa array post atau object dengan key posts.');
        }

        return array_values($records);
    }

    private function category(mixed $value): Category
    {
        $name = is_array($value) ? ($value['name'] ?? null) : $value;
        $name = trim((string) ($name ?: 'Umum'));
        $slug = is_array($value) ? ($value['slug'] ?? null) : null;
        $slug = $this->uniqueTaxonomySlug(Category::class, $slug ?: $name);

        return Category::query()->firstOrCreate([
            'slug' => $slug,
        ], [
            'name' => $name,
            'description' => is_array($value) ? $this->nullableString($value['description'] ?? null) : null,
            'is_active' => true,
            'sort_order' => 0,
        ]);
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
            ->map(function (mixed $value): ?int {
                $name = is_array($value) ? ($value['name'] ?? null) : $value;
                $name = trim((string) $name);

                if ($name === '') {
                    return null;
                }

                $slug = is_array($value) ? ($value['slug'] ?? null) : null;
                $slug = $this->uniqueTaxonomySlug(Tag::class, $slug ?: $name);

                return Tag::query()->firstOrCreate([
                    'slug' => $slug,
                ], [
                    'name' => $name,
                    'description' => is_array($value) ? $this->nullableString($value['description'] ?? null) : null,
                ])->id;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function status(mixed $value): PostStatus
    {
        $status = PostStatus::tryFrom((string) $value);

        return $status ?: PostStatus::Draft;
    }

    private function publishedAt(mixed $value, PostStatus $status): ?Carbon
    {
        if (blank($value)) {
            return $status === PostStatus::Published ? now() : null;
        }

        try {
            return Carbon::parse((string) $value, config('app.timezone', 'Asia/Jakarta'));
        } catch (Throwable $exception) {
            throw new RuntimeException('Format published_at tidak valid.', previous: $exception);
        }
    }

    private function featuredImage(mixed $path, string $extractPath): ?string
    {
        if (blank($path)) {
            return null;
        }

        try {
            $imagePath = $this->resolveImportFile((string) $path, $extractPath);
        } catch (RuntimeException $exception) {
            if ($this->isMissingImageException($exception)) {
                return null;
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

        $document->loadHTML(
            '<!DOCTYPE html><html><body>'.$content.'</body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
        );

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
        $relativePath = $this->safeRelativePath($path);

        if ($relativePath === null) {
            throw new RuntimeException("Path file {$path} tidak valid.");
        }

        $realBase = realpath($extractPath);
        $realPath = realpath($extractPath.DIRECTORY_SEPARATOR.$relativePath);

        if ($realBase === false || $realPath === false || ! str_starts_with($realPath, $realBase.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException("File {$path} tidak ditemukan di dalam ZIP.");
        }

        return $realPath;
    }

    private function safeRelativePath(string $path): ?string
    {
        $path = trim(str_replace('\\', '/', $path));

        if ($path === '' || str_starts_with($path, '/') || preg_match('/^[a-zA-Z]:\//', $path) === 1) {
            return null;
        }

        $segments = array_values(array_filter(explode('/', $path), fn (string $segment): bool => $segment !== ''));

        if ($segments === [] || in_array('..', $segments, true)) {
            return null;
        }

        return implode(DIRECTORY_SEPARATOR, $segments);
    }

    private function isMissingImageException(RuntimeException $exception): bool
    {
        return str_contains($exception->getMessage(), 'tidak ditemukan');
    }

    private function uniquePostSlug(mixed $value): string
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

    /**
     * @param  class-string<Category|Tag>  $model
     */
    private function uniqueTaxonomySlug(string $model, mixed $value): string
    {
        $slug = Str::slug((string) $value) ?: Str::random(8);

        if (! $model::query()->where('slug', $slug)->exists()) {
            return $slug;
        }

        return $slug;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function boolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
    }

    private function userId(mixed $value): ?int
    {
        if (blank($value)) {
            return null;
        }

        $id = (int) $value;

        return User::query()->whereKey($id)->exists() ? $id : null;
    }
}
