<?php

namespace Tests\Feature\Public;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostIndexPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_index_shows_only_published_posts_ordered_by_latest_with_pagination(): void
    {
        $old = Post::factory()->published()->create(['title' => 'Berita Lama', 'published_at' => now()->subDays(2)]);
        $new = Post::factory()->published()->create(['title' => 'Berita Baru', 'published_at' => now()]);
        Post::factory()->count(11)->published()->create();
        Post::factory()->draft()->create(['title' => 'Draft Tidak Tampil']);

        $response = $this->get(route('posts.index'));

        $response->assertOk();
        $response->assertSeeText('Berita Baru');
        $response->assertSeeText('Berita Lama');
        $response->assertDontSeeText('Draft Tidak Tampil');
        $response->assertSee('href="'.route('posts.show', $new->slug).'"', false);
        $response->assertSeeText($new->category->name);
        $response->assertSeeText('Next');

        $secondPage = $this->get(route('posts.index', ['page' => 2]));

        $secondPage->assertOk();
        $secondPage->assertSee('page=2', false);
        $this->assertNotSame($old->id, $new->id);
    }

    public function test_post_index_empty_state(): void
    {
        $this->get(route('posts.index'))
            ->assertOk()
            ->assertSeeText('Belum ada berita');
    }
}
