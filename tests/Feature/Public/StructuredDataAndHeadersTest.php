<?php

namespace Tests\Feature\Public;

use App\Models\Post;
use App\Models\SiteSetting;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StructuredDataAndHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_html_has_security_headers_and_home_structured_data(): void
    {
        config(['app.url' => 'https://portal.test']);
        SiteSetting::factory()->create(['key' => 'site_name', 'value' => 'Portal Test', 'type' => 'string', 'group' => 'general']);
        SiteSetting::factory()->create(['key' => 'facebook_url', 'value' => 'https://facebook.com/portaltest', 'type' => 'url', 'group' => 'social']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertSee('type="application/ld+json"', false);
        $response->assertSee('"@type":"WebSite"', false);
        $response->assertSee('"target":"https://portal.test/cari?q={search_term_string}"', false);
        $response->assertSee('"@type":"NewsMediaOrganization"', false);
        $response->assertSee('https://facebook.com/portaltest', false);
    }

    public function test_post_page_has_article_metadata_and_newsarticle_schema(): void
    {
        config(['app.url' => 'https://portal.test']);
        $tag = Tag::factory()->create(['name' => 'Energi', 'slug' => 'energi']);
        $post = Post::factory()->published()->create([
            'title' => 'Harga Listrik Turun',
            'slug' => 'harga-listrik-turun',
            'excerpt' => 'Ringkasan listrik.',
            'featured_image' => 'posts/featured/listrik.webp',
            'featured_image_alt' => 'Petugas memeriksa jaringan listrik',
            'published_at' => now()->subHour(),
        ]);
        $post->tags()->attach($tag);

        $response = $this->get(route('posts.show', $post->slug));

        $response->assertOk();
        $response->assertSee('property="article:published_time"', false);
        $response->assertSee('property="article:section" content="'.$post->category->name.'"', false);
        $response->assertSee('property="article:tag" content="Energi"', false);
        $response->assertSee('property="og:image:alt" content="Petugas memeriksa jaringan listrik"', false);
        $response->assertSee('"@type":"NewsArticle"', false);
        $response->assertSee('"headline":"Harga Listrik Turun"', false);
        $response->assertSee('"@type":"BreadcrumbList"', false);
    }
}
