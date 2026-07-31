<?php

namespace Tests\Feature\Services;

use App\Models\Post;
use App\Models\PostRedirect;
use App\Services\PostSlugRedirectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class PostSlugRedirectServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_not_changed_does_not_create_redirect(): void
    {
        $post = Post::factory()->create(['slug' => 'slug-lama']);

        $redirect = app(PostSlugRedirectService::class)->handle($post, 'slug-lama', 'slug-lama');

        $this->assertNull($redirect);
        $this->assertDatabaseCount('post_redirects', 0);
    }

    public function test_slug_change_creates_active_301_redirect(): void
    {
        $post = Post::factory()->create(['slug' => 'slug-baru']);

        $redirect = app(PostSlugRedirectService::class)->handle($post, 'slug-lama', 'slug-baru');

        $this->assertSame('/berita/slug-lama', $redirect?->source_path);
        $this->assertSame('/berita/slug-baru', $redirect?->destination_path);
        $this->assertSame(301, $redirect?->status_code);
        $this->assertTrue($redirect?->is_active);
        $this->assertSame($post->id, $redirect?->post_id);
    }

    public function test_second_slug_change_flattens_redirect_chain(): void
    {
        $post = Post::factory()->create(['slug' => 'slug-b']);
        $service = app(PostSlugRedirectService::class);

        $service->handle($post, 'slug-a', 'slug-b');
        $post->forceFill(['slug' => 'slug-c']);
        $service->handle($post, 'slug-b', 'slug-c');

        $this->assertDatabaseHas('post_redirects', [
            'source_path' => '/berita/slug-a',
            'destination_path' => '/berita/slug-c',
        ]);
        $this->assertDatabaseHas('post_redirects', [
            'source_path' => '/berita/slug-b',
            'destination_path' => '/berita/slug-c',
        ]);
    }

    public function test_loop_is_rejected_and_transaction_can_roll_back_post_update(): void
    {
        $post = Post::factory()->create(['slug' => 'slug-a']);
        PostRedirect::factory()->create([
            'source_path' => '/berita/slug-b',
            'destination_path' => '/berita/slug-a',
        ]);

        $this->expectException(InvalidArgumentException::class);

        DB::transaction(function () use ($post): void {
            $post->update(['slug' => 'slug-b']);

            app(PostSlugRedirectService::class)->handle($post, 'slug-a', 'slug-b');
        });
    }

    public function test_force_delete_post_keeps_redirect_and_nulls_relation(): void
    {
        $post = Post::factory()->create();
        $redirect = PostRedirect::factory()->create(['post_id' => $post->id]);

        $post->forceDelete();

        $this->assertDatabaseHas('post_redirects', [
            'id' => $redirect->id,
            'post_id' => null,
        ]);
    }
}
