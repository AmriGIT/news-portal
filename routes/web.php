<?php

use App\Http\Controllers\Admin\PostImportTemplateController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TagController;
use App\Http\Middleware\EnsureAdminLoginEnabled;
use App\Http\Middleware\PublicSecurityHeaders;
use Illuminate\Support\Facades\Route;

$postPrefix = trim(config('content.post_prefix', '/berita'), '/');
$categoryPrefix = trim(config('content.category_prefix', '/kategori'), '/');
$tagPrefix = trim(config('content.tag_prefix', '/tag'), '/');

Route::get('/admin/post-import-template', PostImportTemplateController::class)
    ->middleware([EnsureAdminLoginEnabled::class, 'auth'])
    ->name('admin.posts.import-template');

Route::middleware(PublicSecurityHeaders::class)->group(function () use ($postPrefix, $categoryPrefix, $tagPrefix): void {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/cari', SearchController::class)->name('search');
    Route::get($postPrefix, [PostController::class, 'index'])->name('posts.index');
    Route::get($postPrefix.'/{slug}', [PostController::class, 'show'])->name('posts.show');
    Route::get($categoryPrefix.'/{slug}', [CategoryController::class, 'show'])->name('categories.show');
    Route::get($tagPrefix.'/{slug}', [TagController::class, 'show'])->name('tags.show');
});

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
Route::get('/sitemaps/posts.xml', [SitemapController::class, 'posts'])->name('sitemap.posts');
Route::get('/sitemaps/categories.xml', [SitemapController::class, 'categories'])->name('sitemap.categories');
Route::get('/sitemaps/tags.xml', [SitemapController::class, 'tags'])->name('sitemap.tags');
Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/ads.txt', function () {
    $client = config('services.adsense.client_id');
    $pubId = filled($client) ? preg_replace('/^ca-/', '', $client) : 'pub-0000000000000000';
    $content = "google.com, {$pubId}, DIRECT, f08c47fec0942fa0\n";
    return response($content, 200)->header('Content-Type', 'text/plain');
})->name('ads.txt');
Route::get('/feed', FeedController::class)->name('feed');
Route::redirect('/rss.xml', '/feed')->name('feed.rss');

// GA4 visitor count API (used by dashboard widget)
Route::get('/admin/analytics/ga4', [App\Http\Controllers\AnalyticsController::class, 'ga4Visitors'])
    ->name('admin.analytics.ga4');
