<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\PostImageService;
use App\Services\PublicCacheService;
use Illuminate\Support\Facades\Log;
use Throwable;

class PostObserver
{
    public function saved(Post $post): void
    {
        app(PublicCacheService::class)->flushContentCaches();
    }

    public function deleted(Post $post): void
    {
        app(PublicCacheService::class)->flushContentCaches();
    }

    public function restored(Post $post): void
    {
        app(PublicCacheService::class)->flushContentCaches();
    }

    public function forceDeleted(Post $post): void
    {
        app(PublicCacheService::class)->flushContentCaches();

        try {
            app(PostImageService::class)->deleteWithVariants($post->featured_image);
        } catch (Throwable $exception) {
            Log::warning('Featured image cleanup on force delete failed.', [
                'post_id' => $post->id,
                'path' => $post->featured_image,
                'exception' => $exception::class,
            ]);
        }
    }
}
