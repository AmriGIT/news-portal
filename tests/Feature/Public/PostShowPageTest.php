<?php

namespace Tests\Feature\Public;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostShowPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_post_detail_can_be_opened_and_displays_public_fields(): void
    {
        $tag = Tag::factory()->create(['name' => 'Politik', 'slug' => 'politik']);
        $post = Post::factory()->published()->create([
            'title' => 'Detail Berita Publik',
            'excerpt' => 'Ringkasan berita publik.',
            'content' => '<p>Isi <strong>berita</strong>.</p>',
            'featured_image' => 'posts/featured/image.webp',
            'featured_image_alt' => 'Alt gambar berita',
            'featured_image_caption' => 'Caption gambar',
            'featured_image_credit' => 'Fotografer',
            'published_at' => now(),
        ]);
        $post->tags()->attach($tag);
        $related = Post::factory()->published()->create([
            'category_id' => $post->category_id,
            'title' => 'Berita Terkait',
        ]);
        Post::factory()->published()->create(['title' => 'Kategori Lain']);

        $response = $this->get(route('posts.show', $post->slug));

        $response->assertOk();
        $response->assertSeeText('Detail Berita Publik');
        $response->assertSeeText('Ringkasan berita publik.');
        $response->assertSeeText($post->author->name);
        $response->assertSee('Alt gambar berita');
        $response->assertSeeText('Caption gambar');
        $response->assertSeeText('Fotografer');
        $response->assertSee('<strong>berita</strong>', false);
        $response->assertSeeText('Politik');
        $response->assertSeeText('Berita Terkait');
        $response->assertSee('property="og:type" content="article"', false);
        $response->assertDontSeeText((string) $post->editor_id);
        $response->assertSee('href="'.route('categories.show', $post->category->slug).'"', false);
        $this->assertNotSame($post->id, $related->id);
    }

    public function test_non_public_posts_return_404(): void
    {
        $posts = [
            Post::factory()->draft()->create(),
            Post::factory()->review()->create(),
            Post::factory()->scheduled()->create(),
            Post::factory()->archived()->create(),
            Post::factory()->create([
                'status' => PostStatus::Published,
                'published_at' => now()->addDay(),
            ]),
        ];

        foreach ($posts as $post) {
            $this->get(route('posts.show', $post->slug))->assertNotFound();
        }

        $deleted = Post::factory()->published()->create();
        $deleted->delete();

        $this->get(route('posts.show', $deleted->slug))->assertNotFound();
    }

    public function test_post_without_uploaded_image_uses_public_default_image(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Berita Tanpa Gambar',
            'slug' => 'berita-tanpa-gambar',
            'featured_image' => null,
            'featured_image_alt' => null,
            'og_image' => null,
            'published_at' => now(),
        ]);

        $this->get(route('posts.show', $post->slug))
            ->assertOk()
            ->assertSee('src="http://localhost:8000/images/default.png"', false)
            ->assertSee('property="og:image" content="http://localhost:8000/images/default.png"', false)
            ->assertSee('"image":["http://localhost:8000/images/default.png"', false)
            ->assertDontSee('Gambar berita tidak tersedia');
    }

    public function test_post_detail_uses_multiple_detail_images_when_available(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Berita Dengan Banyak Gambar',
            'slug' => 'berita-dengan-banyak-gambar',
            'featured_image' => 'posts/featured/utama.webp',
            'featured_image_alt' => 'Alt gambar utama',
            'detail_images' => [
                'posts/detail/detail-1.webp',
                'posts/detail/detail-2.webp',
            ],
            'published_at' => now(),
        ]);

        $this->get(route('posts.show', $post->slug))
            ->assertOk()
            ->assertSee('detail-1.webp')
            ->assertSee('detail-2.webp')
            ->assertSee('Alt gambar utama')
            ->assertSee('Alt gambar utama - gambar 2');
    }

    public function test_post_detail_falls_back_to_featured_image_when_detail_images_empty(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Berita Fallback Gambar Utama',
            'slug' => 'berita-fallback-gambar-utama',
            'featured_image' => 'posts/featured/fallback.webp',
            'detail_images' => [],
            'published_at' => now(),
        ]);

        $this->get(route('posts.show', $post->slug))
            ->assertOk()
            ->assertSee('featured/fallback.webp');
    }

    public function test_post_image_alt_never_renders_empty_on_frontend(): void
    {
        config(['media.featured.default_alt' => 'Gambar default portal']);
        $post = Post::factory()->published()->create([
            'title' => '',
            'slug' => 'berita-alt-default',
            'featured_image' => null,
            'featured_image_alt' => '',
            'og_image' => null,
            'published_at' => now(),
        ]);

        $this->get(route('posts.show', $post->slug))
            ->assertOk()
            ->assertSee('alt="Gambar default portal"', false)
            ->assertSee('property="og:image:alt" content="Gambar default portal"', false)
            ->assertDontSee('alt=""', false);
    }
}
