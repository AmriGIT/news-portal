<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\PublicCacheService;

class CategoryObserver
{
    public function saved(Category $category): void
    {
        app(PublicCacheService::class)->flushContentCaches();
    }

    public function deleted(Category $category): void
    {
        app(PublicCacheService::class)->flushContentCaches();
    }
}
