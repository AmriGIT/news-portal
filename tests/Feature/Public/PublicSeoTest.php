<?php

namespace Tests\Feature\Public;

use App\Models\Category;
use App\Models\Post;
use App\Models\SiteSetting;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render_basic_seo_metadata(): void
    {
        SiteSetting::factory()->create([
            'key' => 'site_name',
            'value' => 'Portal Test',
            'type' => 'string',
            'group' => 'general',
        ]);
        SiteSetting::factory()->create([
            'key' => 'default_seo_description',
            'value' => 'Deskripsi <b>default</b>',
            'type' => 'text',
            'group' => 'seo',
        ]);
        $category = Category::factory()->create([
            'name' => 'Bisnis',
            'slug' => 'bisnis',
            'seo_title' => null,
            'seo_description' => null,
        ]);
        $tag = Tag::factory()->create(['name' => 'Pasar', 'slug' => 'pasar']);
        $post = Post::factory()->published()->create([
            'category_id' => $category->id,
            'title' => 'Judul Artikel',
            'slug' => 'judul-artikel',
            'seo_title' => 'SEO Artikel',
            'seo_description' => 'Deskripsi SEO Artikel',
            'og_image' => 'posts/featured/og.webp',
        ]);
        Post::factory()->count(12)->published()->create(['category_id' => $category->id]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<title>', false)
            ->assertSee('rel="canonical"', false)
            ->assertSee('name="robots"', false)
            ->assertDontSee('<b>default</b>', false);

        $this->get(route('posts.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('content="Berita Terbaru | Portal Test"', false)
            ->assertSee('page=2', false);

        $this->get(route('posts.show', $post->slug))
            ->assertOk()
            ->assertSee('<title>SEO Artikel | Portal Test</title>', false)
            ->assertSee('property="og:type" content="article"', false)
            ->assertSee('property="og:image"', false);

        $this->get(route('categories.show', $category->slug))
            ->assertOk()
            ->assertSee('Bisnis | Portal Test');

        $this->get(route('tags.show', $tag->slug))
            ->assertOk()
            ->assertSee('Berita Tag Pasar | Portal Test');
    }
}
