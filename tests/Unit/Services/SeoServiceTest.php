<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\Post;
use App\Services\SeoService;
use App\Services\SiteSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Tests\TestCase;

class SeoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_seo_uses_model_fields_and_cleans_description(): void
    {
        Config::set('app.url', 'https://portal.test');
        app(SiteSettingService::class)->set('site_name', 'Portal');
        $post = Post::factory()->published()->create([
            'title' => 'Judul Berita',
            'slug' => 'judul-berita',
            'seo_title' => 'Judul SEO',
            'seo_description' => null,
            'excerpt' => null,
            'content' => '<p>Isi <strong>berita</strong> &amp; ringkasan.</p>',
            'canonical_url' => null,
            'featured_image' => 'posts/featured/image.webp',
            'robots_index' => true,
            'robots_follow' => false,
        ]);

        $seo = app(SeoService::class)->forPost($post);

        $this->assertSame('Judul SEO | Portal', $seo->title);
        $this->assertSame('Isi berita & ringkasan.', $seo->description);
        $this->assertSame('https://portal.test/berita/judul-berita', $seo->canonicalUrl);
        $this->assertTrue($seo->robotsIndex);
        $this->assertFalse($seo->robotsFollow);
        $this->assertSame('article', $seo->ogType);
        $this->assertStringContainsString('/storage/posts/featured/image.webp', $seo->ogImage ?? '');
        $this->assertSame('summary_large_image', $seo->twitterCard);
    }

    public function test_non_published_post_and_inactive_category_are_noindex(): void
    {
        $draft = Post::factory()->draft()->create();
        $archived = Post::factory()->archived()->create();
        $inactiveCategory = Category::factory()->inactive()->create();

        $service = app(SeoService::class);

        $this->assertFalse($service->forPost($draft)->robotsIndex);
        $this->assertFalse($service->forPost($draft)->robotsFollow);
        $this->assertFalse($service->forPost($archived)->robotsIndex);
        $this->assertTrue($service->forPost($archived)->robotsFollow);
        $this->assertFalse($service->forCategory($inactiveCategory)->robotsIndex);
        $this->assertFalse($service->forCategory($inactiveCategory)->robotsFollow);
    }

    public function test_home_seo_uses_defaults_and_dangerous_canonical_is_rejected(): void
    {
        app(SiteSettingService::class)->setMany([
            'site_name' => 'Portal',
            'default_seo_title' => 'Beranda',
            'default_seo_description' => 'Deskripsi default',
        ]);

        $home = app(SeoService::class)->forHome();

        $this->assertSame('Beranda | Portal', $home->title);
        $this->assertSame('Deskripsi default', $home->description);
        $this->assertSame('website', $home->ogType);

        $this->expectException(InvalidArgumentException::class);

        app(SeoService::class)->forPost(Post::factory()->published()->make([
            'canonical_url' => 'javascript:alert(1)',
            'slug' => 'x',
        ]));
    }
}
