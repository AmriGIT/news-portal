<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Cache;

class PublicCacheService
{
    public const NAVIGATION_CATEGORIES = 'public.v2.navigation.category_ids';

    public const HOMEPAGE = 'public.v2.homepage';

    public const SITEMAP_INDEX = 'public.v1.sitemap.index';

    public const SITEMAP_POSTS = 'public.v1.sitemap.posts';

    public const SITEMAP_CATEGORIES = 'public.v1.sitemap.categories';

    public const SITEMAP_TAGS = 'public.v1.sitemap.tags';

    public const FEED = 'public.v1.feed';

    public function navigationTtl(): \DateTimeInterface
    {
        return now()->addMinutes(30);
    }

    public function homepageTtl(): \DateTimeInterface
    {
        return now()->addMinutes(5);
    }

    public function sitemapTtl(): \DateTimeInterface
    {
        return now()->addMinutes(15);
    }

    public function feedTtl(): \DateTimeInterface
    {
        return now()->addMinutes(5);
    }

    public function forgetNavigation(): void
    {
        Cache::forget(self::NAVIGATION_CATEGORIES);
        Cache::forget('public.v1.navigation.categories');
    }

    public function forgetHomepage(): void
    {
        Cache::forget(self::HOMEPAGE);
        Cache::forget('public.v1.homepage');
    }

    public function forgetSitemap(): void
    {
        Cache::forget(self::SITEMAP_INDEX);
        Cache::forget(self::SITEMAP_POSTS);
        Cache::forget(self::SITEMAP_CATEGORIES);
        Cache::forget(self::SITEMAP_TAGS);
    }

    public function forgetFeed(): void
    {
        Cache::forget(self::FEED);
    }

    public function forgetPost(Post $post): void
    {
        Cache::forget('public.v1.post.'.$post->id);
    }

    public function flushContentCaches(): void
    {
        $this->forgetNavigation();
        $this->forgetHomepage();
        $this->forgetSitemap();
        $this->forgetFeed();
    }
}
