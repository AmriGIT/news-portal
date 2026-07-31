<?php

namespace Tests\Feature\Public;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\PublicCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SitemapFeedRobotsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemaps_include_only_indexable_public_content(): void
    {
        config(['app.url' => 'https://portal.test']);
        Cache::flush();
        $category = Category::factory()->create(['name' => 'Bisnis', 'slug' => 'bisnis']);
        $emptyCategory = Category::factory()->create(['name' => 'Kosong', 'slug' => 'kosong']);
        $tag = Tag::factory()->create(['name' => 'Pasar', 'slug' => 'pasar']);
        $emptyTag = Tag::factory()->create(['name' => 'Sepi', 'slug' => 'sepi']);
        $publicPost = Post::factory()->published()->create([
            'category_id' => $category->id,
            'title' => 'Masuk Sitemap',
            'slug' => 'masuk-sitemap',
            'published_at' => now(),
        ]);
        $publicPost->tags()->attach($tag);
        Post::factory()->draft()->create(['title' => 'Draft Sitemap', 'slug' => 'draft-sitemap']);
        Post::factory()->published()->create(['title' => 'Noindex Sitemap', 'slug' => 'noindex-sitemap', 'robots_index' => false]);
        Post::factory()->published()->create(['title' => 'Kanonikal Eksternal', 'slug' => 'kanonikal-eksternal', 'canonical_url' => 'https://example.com/berita']);

        $this->get(route('sitemap.index'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('https://portal.test/sitemaps/posts.xml', false);

        $this->get(route('sitemap.posts'))
            ->assertOk()
            ->assertSee('https://portal.test/berita/masuk-sitemap', false)
            ->assertDontSee('draft-sitemap', false)
            ->assertDontSee('noindex-sitemap', false)
            ->assertDontSee('kanonikal-eksternal', false);

        $this->get(route('sitemap.categories'))
            ->assertOk()
            ->assertSee('https://portal.test/kategori/bisnis', false)
            ->assertDontSee('kosong', false);

        $this->get(route('sitemap.tags'))
            ->assertOk()
            ->assertSee('https://portal.test/tag/pasar', false)
            ->assertDontSee($emptyTag->slug, false);

        $this->assertTrue(Cache::has(PublicCacheService::SITEMAP_POSTS));
        $this->assertFalse($emptyCategory->posts()->exists());
    }

    public function test_feed_contains_limited_public_items_and_robots_txt_is_dynamic(): void
    {
        config(['app.url' => 'https://portal.test']);
        Cache::flush();
        Post::factory()->published()->create(['title' => 'Feed Terbaru', 'slug' => 'feed-terbaru', 'published_at' => now()]);
        Post::factory()->count(20)->published()->create(['published_at' => now()->subDay()]);
        Post::factory()->draft()->create(['title' => 'Feed Draft']);
        Post::factory()->published()->create(['title' => 'Feed Noindex', 'robots_index' => false]);

        $feed = $this->get(route('feed'));

        $feed->assertOk();
        $feed->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
        $feed->assertSee('<rss version="2.0">', false);
        $feed->assertSeeText('Feed Terbaru');
        $feed->assertDontSeeText('Feed Draft');
        $feed->assertDontSeeText('Feed Noindex');
        $this->assertSame(20, substr_count($feed->getContent(), '<item>'));
        $this->assertTrue(Cache::has(PublicCacheService::FEED));

        $this->get(route('robots'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee("User-agent: *\nDisallow: /", false);

        $this->get(route('feed.rss'))->assertRedirect('/feed');
    }
}
