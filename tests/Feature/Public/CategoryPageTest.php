<?php

namespace Tests\Feature\Public;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_category_page_lists_only_published_posts_in_category(): void
    {
        $category = Category::factory()->create(['name' => 'Nasional', 'slug' => 'nasional']);
        $target = Post::factory()->published()->create(['category_id' => $category->id, 'title' => 'Berita Nasional']);
        Post::factory()->draft()->create(['category_id' => $category->id, 'title' => 'Draft Nasional']);
        Post::factory()->published()->create(['title' => 'Kategori Lain']);

        $response = $this->get(route('categories.show', $category->slug));

        $response->assertOk();
        $response->assertSeeText('Nasional');
        $response->assertSeeText('Berita Nasional');
        $response->assertDontSeeText('Draft Nasional');
        $response->assertDontSeeText('Kategori Lain');
        $response->assertSee('href="'.route('posts.show', $target->slug).'"', false);
        $response->assertSeeText('Beranda');
    }

    public function test_inactive_category_returns_404_and_empty_category_shows_empty_state(): void
    {
        $inactive = Category::factory()->inactive()->create();
        $empty = Category::factory()->create(['name' => 'Kosong', 'slug' => 'kosong']);

        $this->get(route('categories.show', $inactive->slug))->assertNotFound();
        $this->get(route('categories.show', $empty->slug))
            ->assertOk()
            ->assertSeeText('Belum ada berita di kategori ini');
    }
}
