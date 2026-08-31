<?php

namespace Tests\Unit\Services;

use App\Services\PostImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class PostImageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_featured_image_and_variants_with_safe_names(): void
    {
        Storage::fake('public');
        $this->travelTo(now()->setDate(2026, 7, 30));

        $file = UploadedFile::fake()->image('foto-asli.jpg', 1800, 1200)->size(1000);

        $paths = app(PostImageService::class)->storeFeaturedImage($file);

        $this->assertArrayHasKey('original', $paths);
        $this->assertStringStartsWith('posts/featured/2026/07/', $paths['original']);
        $this->assertStringEndsWith('.webp', $paths['original']);
        $this->assertStringNotContainsString('foto-asli', $paths['original']);

        foreach ($paths as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_it_upscales_small_featured_images_to_standard_dimensions(): void
    {
        Storage::fake('public');

        $paths = app(PostImageService::class)->storeFeaturedImage(
            UploadedFile::fake()->image('small.jpg', 600, 400)->size(1000)
        );

        Storage::disk('public')->assertExists($paths['original']);
        [$width, $height] = getimagesize(Storage::disk('public')->path($paths['original']));

        $this->assertSame(1600, $width);
        $this->assertSame(900, $height);
    }

    public function test_it_rejects_non_allowed_mime_types(): void
    {
        Storage::fake('public');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Format gambar harus JPG, JPEG, PNG, atau WebP.');

        app(PostImageService::class)->storeFeaturedImage(
            UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml')
        );
    }

    public function test_it_rejects_featured_images_larger_than_five_megabytes(): void
    {
        Storage::fake('public');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Ukuran gambar maksimal 5 MB.');

        app(PostImageService::class)->storeFeaturedImage(
            UploadedFile::fake()->image('large.jpg', 1800, 1200)->size(5121)
        );
    }

    public function test_it_stores_content_image_without_variants(): void
    {
        Storage::fake('public');
        $this->travelTo(now()->setDate(2026, 7, 30));

        $path = app(PostImageService::class)->storeContentImage(
            UploadedFile::fake()->image('content.png', 2400, 1600)->size(1000)
        );

        $this->assertStringStartsWith('posts/content/2026/07/', $path);
        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_it_deletes_featured_image_with_variants(): void
    {
        Storage::fake('public');
        $service = app(PostImageService::class);
        $paths = $service->storeFeaturedImage(UploadedFile::fake()->image('image.jpg', 1800, 1200)->size(1000));

        $service->deleteWithVariants($paths['original']);

        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }
}
