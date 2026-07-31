<?php

namespace Tests\Feature\Public;

use App\Models\Category;
use App\Models\Post;
use App\Services\PublicCacheService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_featured_post_latest_posts_and_only_published_content(): void
    {
        $featured = Post::factory()->published()->featured()->create([
            'title' => 'Berita Utama Publik',
            'published_at' => now()->subHour(),
        ]);
        $latest = Post::factory()->published()->create([
            'title' => 'Berita Terbaru Publik',
            'published_at' => now(),
        ]);
        Post::factory()->draft()->create(['title' => 'Draft Rahasia']);
        Post::factory()->review()->create(['title' => 'Review Rahasia']);
        Post::factory()->scheduled()->create(['title' => 'Scheduled Rahasia']);
        Post::factory()->archived()->create(['title' => 'Archived Rahasia']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSeeText('Berita Utama Publik');
        $response->assertSeeText('Berita Terbaru Publik');
        $response->assertDontSeeText('Draft Rahasia');
        $response->assertDontSeeText('Review Rahasia');
        $response->assertDontSeeText('Scheduled Rahasia');
        $response->assertDontSeeText('Archived Rahasia');
        $response->assertSee('href="'.route('posts.show', $featured->slug).'"', false);
        $response->assertSee('id="main-content"', false);
        $response->assertSee('images/header', false);
        $response->assertSee('logo.png', false);
        $response->assertSee('images/favicon.png', false);
        $response->assertSee('object-cover object-center', false);
    }

    public function test_homepage_falls_back_to_latest_post_and_handles_empty_state(): void
    {
        $latest = Post::factory()->published()->create([
            'title' => 'Latest Tanpa Featured',
            'published_at' => now(),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSeeText('Latest Tanpa Featured');

        Post::query()->delete();
        Cache::flush();

        $this->get(route('home'))
            ->assertOk()
            ->assertSeeText('Belum ada berita terbit');
    }

    public function test_navigation_categories_are_active_limited_and_cached(): void
    {
        Cache::flush();
        Category::factory()->count(7)->sequence(
            ['name' => 'Kategori 1', 'slug' => 'kategori-1', 'is_active' => true, 'sort_order' => 1],
            ['name' => 'Kategori 2', 'slug' => 'kategori-2', 'is_active' => true, 'sort_order' => 2],
            ['name' => 'Kategori 3', 'slug' => 'kategori-3', 'is_active' => true, 'sort_order' => 3],
            ['name' => 'Kategori 4', 'slug' => 'kategori-4', 'is_active' => true, 'sort_order' => 4],
            ['name' => 'Kategori 5', 'slug' => 'kategori-5', 'is_active' => true, 'sort_order' => 5],
            ['name' => 'Kategori 6', 'slug' => 'kategori-6', 'is_active' => true, 'sort_order' => 6],
            ['name' => 'Kategori 7', 'slug' => 'kategori-7', 'is_active' => true, 'sort_order' => 7],
        )->create();
        Category::factory()->inactive()->create(['name' => 'Kategori Nonaktif', 'slug' => 'kategori-nonaktif']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSeeText('Kategori 1');
        $response->assertDontSeeText('Kategori Nonaktif');
        $this->assertTrue(Cache::has(PublicCacheService::NAVIGATION_CATEGORIES));
        $this->assertContainsOnly('int', Cache::get(PublicCacheService::NAVIGATION_CATEGORIES));
        $this->assertNotInstanceOf(EloquentCollection::class, Cache::get(PublicCacheService::NAVIGATION_CATEGORIES));
    }

    public function test_homepage_cache_stores_scalar_payload_instead_of_eloquent_models(): void
    {
        Cache::flush();
        $post = Post::factory()->published()->featured()->create([
            'title' => 'Berita Cache Aman',
            'published_at' => now(),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSeeText('Berita Cache Aman');

        $payload = Cache::get(PublicCacheService::HOMEPAGE);

        $this->assertIsArray($payload);
        $this->assertSame($post->id, $payload['heroPostId']);
        $this->assertIsArray($payload['featuredPostIds']);
        $this->assertIsArray($payload['latestPostIds']);
        $this->assertIsArray($payload['categorySections']);
        $this->assertNotInstanceOf(EloquentCollection::class, $payload);
    }
}
