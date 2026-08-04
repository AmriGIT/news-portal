<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use App\Services\ContentSanitizer;
use App\Services\PostImageService;
use App\Services\PostSlugRedirectService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Lihat'),

            ...PostResource::getWorkflowActions(),

            DeleteAction::make()
                ->label('Hapus')
                ->visible(fn (Post $record): bool => auth()->user()?->can('delete', $record) ?? false)
                ->successNotificationTitle('Berita berhasil dihapus.'),

            RestoreAction::make()
                ->label('Pulihkan')
                ->visible(fn (Post $record): bool => auth()->user()?->can('restore', $record) ?? false)
                ->successNotificationTitle('Berita berhasil dipulihkan.'),

            ForceDeleteAction::make()
                ->label('Hapus Permanen')
                ->visible(fn (Post $record): bool => auth()->user()?->can('forceDelete', $record) ?? false)
                ->successNotificationTitle('Berita berhasil dihapus permanen.'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        $data['author_id'] = $this->record->author_id;
        $data['editor_id'] = $user?->isAdmin() ? ($data['editor_id'] ?? $this->record->editor_id) : $this->record->editor_id;
        $data['status'] = $this->record->status;
        $data['published_at'] = $this->record->published_at;
        $data['featured_image'] = $this->normalizeFeaturedImage($data['featured_image'] ?? null, $this->record->featured_image);
        $data['detail_images'] = $this->normalizeDetailImages($data['detail_images'] ?? []);

        if (isset($data['content'])) {
            $data['content'] = app(ContentSanitizer::class)->sanitize($data['content']);
        }

        if (! $user?->isAdmin()) {
            $data['is_featured'] = $this->record->is_featured;
            $data['robots_index'] = $this->record->robots_index;
            $data['robots_follow'] = $this->record->robots_follow;
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $oldFeaturedImage = $record->featured_image;
        $newFeaturedImage = $data['featured_image'] ?? null;
        $oldDetailImages = $this->normalizeDetailImages($record->detail_images ?? []);
        $newDetailImages = $this->normalizeDetailImages($data['detail_images'] ?? []);
        $oldSlug = $record->slug;

        try {
            DB::transaction(function () use ($record, $data, $oldSlug): void {
                $record->update($data);

                app(PostSlugRedirectService::class)->handle(
                    post: $record,
                    oldSlug: $oldSlug,
                    newSlug: $record->slug,
                );
            });
        } catch (Throwable $exception) {
            if ($newFeaturedImage !== $oldFeaturedImage) {
                app(PostImageService::class)->deleteWithVariants($newFeaturedImage);
            }
            $this->deleteDetailImages(array_diff($newDetailImages, $oldDetailImages));

            throw $exception;
        }

        if ($newFeaturedImage !== $oldFeaturedImage) {
            app(PostImageService::class)->deleteWithVariants($oldFeaturedImage);
        }
        $this->deleteDetailImages(array_diff($oldDetailImages, $newDetailImages));

        return $record->refresh();
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Berita berhasil diperbarui.';
    }

    private function normalizeFeaturedImage(mixed $value, ?string $currentPath = null): ?string
    {
        if (is_array($value)) {
            $files = array_values(array_filter($value));

            if (filled($currentPath)) {
                $replacement = collect($files)
                    ->map(fn (mixed $file): string => (string) $file)
                    ->first(fn (string $file): bool => $file !== $currentPath);

                if (filled($replacement)) {
                    return $replacement;
                }
            }

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
