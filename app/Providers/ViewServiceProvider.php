<?php

namespace App\Providers;

use App\Models\Category;
use App\Services\ContentUrlService;
use App\Services\PublicCacheService;
use App\Services\SiteSettingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(SiteSettingService $settings, ContentUrlService $urls): void
    {
        View::composer(['layouts.public', 'errors.404'], function ($view) use ($settings, $urls): void {
            $siteLogo = $settings->get('site_logo');
            $siteFavicon = $settings->get('site_favicon');

            $view->with('publicSite', [
                'name' => $settings->siteName(),
                'tagline' => $settings->get('site_tagline'),
                'description' => $settings->siteDescription(),
                'logoUrl' => $urls->storageUrl(filled($siteLogo) ? (string) $siteLogo : null),
                'faviconUrl' => filled($siteFavicon) ? $urls->storageUrl((string) $siteFavicon) : asset('images/favicon.png'),
                'contactEmail' => $settings->get('contact_email'),
                'contactPhone' => $settings->get('contact_phone'),
                'contactAddress' => $settings->get('contact_address'),
                'footerText' => $settings->get('footer_text'),
                'socialLinks' => array_filter([
                    'Facebook' => $settings->get('facebook_url'),
                    'Instagram' => $settings->get('instagram_url'),
                    'YouTube' => $settings->get('youtube_url'),
                    'X' => $settings->get('x_url'),
                    'TikTok' => $settings->get('tiktok_url'),
                ]),
            ]);
        });

        View::composer('components.public.header', function ($view): void {
            $cache = app(PublicCacheService::class);
            $categoryIds = Cache::remember(PublicCacheService::NAVIGATION_CATEGORIES, $cache->navigationTtl(), fn (): array => Category::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit(6)
                ->pluck('id')
                ->all());

            $categories = Category::query()
                ->select(['id', 'name', 'slug'])
                ->whereIn('id', $categoryIds)
                ->get()
                ->keyBy('id');

            $view->with('navigationCategories', collect($categoryIds)
                ->map(fn (int $id) => $categories->get($id))
                ->filter()
                ->values());
        });
    }
}
