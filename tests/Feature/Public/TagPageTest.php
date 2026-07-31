<?php

namespace Tests\Feature\Public;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_page_lists_only_published_posts_with_tag(): void
    {
        $tag = Tag::factory()->create(['name' => 'Teknologi', 'slug' => 'teknologi']);
        $target = Post::factory()->published()->create(['title' => 'Berita Teknologi']);
        $draft = Post::factory()->draft()->create(['title' => 'Draft Teknologi']);
        $other = Post::factory()->published()->create(['title' => 'Berita Lain']);

        $target->tags()->attach($tag);
        $draft->tags()->attach($tag);

        $response = $this->get(route('tags.show', $tag->slug));

        $response->assertOk();
        $response->assertSeeText('Teknologi');
        $response->assertSeeText('Berita Teknologi');
        $response->assertDontSeeText('Draft Teknologi');
        $response->assertDontSeeText('Berita Lain');
        $this->assertNotSame($target->id, $other->id);
    }

    public function test_tag_not_found_returns_404_and_valid_empty_tag_shows_empty_state(): void
    {
        $tag = Tag::factory()->create(['name' => 'Kosong', 'slug' => 'kosong']);

        $this->get('/tag/tidak-ada')->assertNotFound();
        $this->get(route('tags.show', $tag->slug))
            ->assertOk()
            ->assertSeeText('Belum ada berita dengan tag ini');
    }
}
