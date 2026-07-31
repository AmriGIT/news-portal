<?php

namespace Tests\Feature\Public;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_page_finds_public_posts_and_normalizes_keyword(): void
    {
        $category = Category::factory()->create(['name' => 'Teknologi']);
        $match = Post::factory()->published()->create([
            'category_id' => $category->id,
            'title' => 'Ekonomi Digital Nasional',
            'excerpt' => 'Transformasi pembayaran digital.',
            'content' => '<p>Konten fintech aman.</p>',
            'published_at' => now(),
        ]);
        Post::factory()->draft()->create(['title' => 'Ekonomi Digital Draft']);

        $response = $this->get(route('search', ['q' => " Ekonomi\tDigital "]));

        $response->assertOk();
        $response->assertSeeText('Ekonomi Digital Nasional');
        $response->assertDontSeeText('Ekonomi Digital Draft');
        $response->assertSee('content="noindex, follow"', false);
        $response->assertSee('q=Ekonomi%20Digital', false);
        $response->assertSee('href="'.route('posts.show', $match->slug).'"', false);
    }

    public function test_search_validation_wildcards_and_empty_pagination_are_handled(): void
    {
        Post::factory()->published()->create(['title' => 'Diskon 100 Ribu']);
        Post::factory()->published()->create(['title' => 'Diskon 100% Publik']);

        $this->from(route('search'))
            ->get(route('search', ['q' => 'a']))
            ->assertRedirect(route('search'))
            ->assertSessionHasErrors('q');

        $this->get(route('search', ['q' => '100%']))
            ->assertOk()
            ->assertSeeText('Diskon 100% Publik')
            ->assertDontSeeText('Diskon 100 Ribu');

        $this->get(route('search', ['q' => 'tidak ada', 'page' => 2]))
            ->assertNotFound();
    }
}
