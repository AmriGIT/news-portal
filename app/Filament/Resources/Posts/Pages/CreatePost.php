<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Services\ContentSanitizer;
use App\Services\PostImageService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        $data['author_id'] = $user?->isEditor() ? $user->id : ($data['author_id'] ?? $user?->id);
        $data['editor_id'] = $user?->isEditor() ? null : ($data['editor_id'] ?? null);
        $data['status'] = PostStatus::Draft;
        $data['published_at'] = null;
        $data['is_featured'] = $user?->isAdmin() ? ($data['is_featured'] ?? false) : false;
        $data['robots_index'] = $user?->isAdmin() ? ($data['robots_index'] ?? true) : true;
        $data['robots_follow'] = $user?->isAdmin() ? ($data['robots_follow'] ?? true) : true;
        $data['featured_image'] = $this->normalizeFeaturedImage($data['featured_image'] ?? null);
        $data['detail_images'] = $this->normalizeDetailImages($data['detail_images'] ?? []);

        if (isset($data['content'])) {
            $data['content'] = app(ContentSanitizer::class)->sanitize($data['content']);
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return DB::transaction(fn (): Model => parent::handleRecordCreation($data));
        } catch (Throwable $exception) {
            app(PostImageService::class)->deleteWithVariants($data['featured_image'] ?? null);
            $this->deleteDetailImages($data['detail_images'] ?? []);

            throw $exception;
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Berita berhasil dibuat sebagai draf.';
    }

    private function normalizeFeaturedImage(mixed $value): ?string
    {
        if (is_array($value)) {
            $files = array_values(array_filter($value));

            return filled($files) ? (string) end($files) : null;
        }

        return filled($value) ? (string) $value : null;
    }

    /**
     * @return array<int, string>
     */
    private function normalizeDetailImages(mixed $value): array
    {
        return collect(is_array($value) ? $value : [])
            ->map(fn (mixed $path): string => (string) $path)
            ->filter(fn (string $path): bool => filled($path))
            ->values()
            ->all();
    }

    /**
     * @param  iterable<string>  $paths
     */
    private function deleteDetailImages(iterable $paths): void
    {
        foreach ($paths as $path) {
            app(PostImageService::class)->deleteWithVariants($path);
        }
    }
}
