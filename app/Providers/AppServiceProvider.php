<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\ImportToken;
use App\Models\NewsImport;
use App\Models\Post;
use App\Models\PostRedirect;
use App\Models\SiteSetting;
use App\Models\Tag;
use App\Models\User;
use App\Observers\CategoryObserver;
use App\Observers\PostObserver;
use App\Observers\TagObserver;
use App\Policies\CategoryPolicy;
use App\Policies\ImportTokenPolicy;
use App\Policies\NewsImportPolicy;
use App\Policies\PostPolicy;
use App\Policies\PostRedirectPolicy;
use App\Policies\SiteSettingPolicy;
use App\Policies\TagPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
    public function boot(): void
    {
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(PostRedirect::class, PostRedirectPolicy::class);
        Gate::policy(SiteSetting::class, SiteSettingPolicy::class);
        Gate::policy(ImportToken::class, ImportTokenPolicy::class);
        Gate::policy(NewsImport::class, NewsImportPolicy::class);

        RateLimiter::for('news-import', function (Request $request): Limit {
            return Limit::perMinute((int) config('news-import.rate_limit', 10))
                ->by($request->bearerToken() ? sha1($request->bearerToken()) : $request->ip());
        });

        Post::observe(PostObserver::class);
        Category::observe(CategoryObserver::class);
        Tag::observe(TagObserver::class);
    }
}
