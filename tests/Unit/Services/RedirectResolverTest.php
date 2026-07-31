<?php

namespace Tests\Unit\Services;

use App\Models\PostRedirect;
use App\Services\RedirectResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolver_finds_active_redirect_and_normalizes_input(): void
    {
        $redirect = PostRedirect::factory()->create([
            'source_path' => '/berita/url-lama',
            'destination_path' => '/berita/url-baru',
        ]);

        $resolved = app(RedirectResolver::class)->resolve('/berita//url-lama/');

        $this->assertTrue($redirect->is($resolved));
    }

    public function test_resolver_ignores_inactive_redirect_and_records_hit_separately(): void
    {
        $redirect = PostRedirect::factory()->inactive()->create([
            'source_path' => '/berita/nonaktif',
        ]);

        $this->assertNull(app(RedirectResolver::class)->resolve('/berita/nonaktif'));

        app(RedirectResolver::class)->recordHit($redirect);

        $redirect->refresh();

        $this->assertSame(1, $redirect->hit_count);
        $this->assertNotNull($redirect->last_hit_at);
    }
}
