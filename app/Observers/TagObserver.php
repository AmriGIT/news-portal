<?php

namespace App\Observers;

use App\Models\Tag;
use App\Services\PublicCacheService;

class TagObserver
{
    public function saved(Tag $tag): void
    {
        app(PublicCacheService::class)->flushContentCaches();
    }

    public function deleted(Tag $tag): void
    {
        app(PublicCacheService::class)->flushContentCaches();
    }
}
