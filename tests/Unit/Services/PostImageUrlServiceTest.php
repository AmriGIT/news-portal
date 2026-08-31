<?php

namespace Tests\Unit\Services;

use App\Services\PostImageUrlService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PostImageUrlServiceTest extends TestCase
{
    public function test_it_builds_variant_urls_and_srcset(): void
    {
        Config::set('app.url', 'https://portal.test');
        Config::set('filesystems.disks.public.url', 'https://portal.test/storage');

        $service = new PostImageUrlService;
        $path = 'posts/featured/2026/07/image.webp';

        $this->assertSame('https://portal.test/storage/posts/featured/2026/07/image.webp', $service->original($path));
        $this->assertSame('https://portal.test/storage/posts/featured/2026/07/image-large.webp', $service->large($path));
        $this->assertStringContainsString('image-thumbnail.webp 480w', $service->srcsetAttribute($path) ?? '');
    }

    public function test_it_returns_default_image_for_empty_path_and_keeps_absolute_url(): void
    {
        Config::set('app.url', 'https://portal.test');
        $service = new PostImageUrlService;

        $this->assertSame('https://portal.test/images/default.png', $service->original(null));
        $this->assertStringContainsString('/images/default.png 480w', $service->srcsetAttribute(null) ?? '');
        $this->assertSame('https://cdn.test/image.webp', $service->original('https://cdn.test/image.webp'));
    }

    public function test_it_forces_storage_urls_to_absolute_urls(): void
    {
        Config::set('app.url', 'https://portal.test');
        Config::set('filesystems.disks.public.url', '/storage');

        $service = new PostImageUrlService;

        $this->assertSame('https://portal.test/storage/posts/featured/image.webp', $service->original('posts/featured/image.webp'));
        $this->assertStringContainsString('https://portal.test/storage/posts/featured/image-thumbnail.webp 480w', $service->srcsetAttribute('posts/featured/image.webp') ?? '');
    }

    public function test_public_storage_url_override_controls_generated_storage_urls(): void
    {
        Config::set('app.url', 'https://www.portal.test');
        Config::set('filesystems.disks.public.url', 'https://www.portal.test/storage');

        $service = new PostImageUrlService;

        $this->assertSame(
            'https://www.portal.test/storage/posts/featured/image.webp',
            $service->original('posts/featured/image.webp')
        );
    }

    public function test_default_image_url_comes_from_media_config(): void
    {
        Config::set('app.url', 'https://portal.test');
        Config::set('media.featured.default_image', '/assets/news-default.png');

        $service = new PostImageUrlService;

        $this->assertSame('https://portal.test/assets/news-default.png', $service->original(null));
        $this->assertSame('https://portal.test/assets/news-default.png', $service->large(null));
        $this->assertSame('https://portal.test/assets/news-default.png', $service->medium(null));
        $this->assertSame('https://portal.test/assets/news-default.png', $service->thumbnail(null));
    }

    public function test_alt_text_falls_back_to_post_title_then_default_alt(): void
    {
        Config::set('media.featured.default_alt', 'Gambar default portal');

        $service = new PostImageUrlService;

        $this->assertSame('Alt manual', $service->alt(' Alt manual ', 'Judul berita'));
        $this->assertSame('Judul berita', $service->alt(null, 'Judul berita'));
        $this->assertSame('Gambar default portal', $service->alt(null, null));
    }
}
