<?php

namespace Tests\Feature\Public;

use App\Models\Post;
use App\Models\PostRedirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_slug_redirects_to_destination_with_status_code(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'slug-baru']);
        PostRedirect::factory()->create([
            'post_id' => $post->id,
            'source_path' => '/berita/slug-lama',
            'destination_path' => '/berita/slug-baru',
            'status_code' => 301,
        ]);

        $response = $this->get('/berita/slug-lama');

        $response->assertRedirect('/berita/slug-baru');
        $this->assertSame(301, $response->status());
        $this->assertSame(1, PostRedirect::query()->where('source_path', '/berita/slug-lama')->value('hit_count'));
    }

    public function test_manual_temporary_redirect_uses_302_and_inactive_redirect_returns_404(): void
    {
        PostRedirect::factory()->temporary()->create([
            'source_path' => '/berita/manual-lama',
            'destination_path' => '/berita/manual-baru',
        ]);
        PostRedirect::factory()->inactive()->create([
            'source_path' => '/berita/nonaktif',
            'destination_path' => '/berita/tujuan',
        ]);

        $temporary = $this->get('/berita/manual-lama');

        $temporary->assertRedirect('/berita/manual-baru');
        $this->assertSame(302, $temporary->status());
        $this->get('/berita/nonaktif')->assertNotFound();
    }

    public function test_redirect_resolver_is_not_used_when_post_exists(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'slug-sama']);
        PostRedirect::factory()->create([
            'source_path' => '/berita/slug-sama',
            'destination_path' => '/berita/tujuan-lain',
        ]);

        $this->get(route('posts.show', $post->slug))
            ->assertOk()
            ->assertSeeText($post->title);
    }
}
