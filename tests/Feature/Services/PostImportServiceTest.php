<?php

namespace Tests\Feature\Services;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use App\Services\PostImageService;
use App\Services\PostImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

class PostImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_posts_from_zip_with_featured_and_content_images(): void
    {
        if (! class_exists(ZipArchive::class) || ! extension_loaded('gd')) {
            $this->markTestSkipped('ZipArchive atau GD belum tersedia.');
        }

        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $workspace = storage_path('framework/testing/imports/'.Str::uuid());
        File::ensureDirectoryExists($workspace);

        try {
            $featuredPath = $workspace.'/featured.jpg';
            $contentPath = $workspace.'/content.jpg';
            $zipPath = $workspace.'/posts.zip';

            $this->makeImage($featuredPath, 1600, 900);
            $this->makeImage($contentPath, 1200, 675);
            $this->makeZip($zipPath, [
                'posts' => [
                    [
                        'title' => 'Berita Import Dengan Gambar',
                        'slug' => 'berita-import-dengan-gambar',
                        'excerpt' => 'Ringkasan import.',
                        'content' => '<p>Konten import.</p><p><img src="images/content.jpg" alt="Foto konten"></p>',
                        'category' => 'Nasional',
                        'tags' => ['Import', 'SEO'],
                        'status' => PostStatus::Published->value,
                        'published_at' => '2026-07-31 08:00:00',
                        'featured_image' => 'images/featured.jpg',
                        'featured_image_alt' => 'Foto utama import',
                    ],
                ],
            ], [
                'images/featured.jpg' => $featuredPath,
                'images/content.jpg' => $contentPath,
            ]);

            $result = app(PostImportService::class)->importFromZip($zipPath, $admin);

            $this->assertSame(1, $result['imported']);
            $this->assertSame(0, $result['failed']);

            $post = Post::query()->where('slug', 'berita-import-dengan-gambar')->firstOrFail();

            $this->assertSame($admin->id, $post->author_id);
            $this->assertSame('Nasional', $post->category->name);
            $this->assertSame(PostStatus::Published, $post->status);
            $this->assertNotNull($post->published_at);
            $this->assertNotNull($post->featured_image);
            $this->assertStringContainsString('posts/content', $post->content);
            $this->assertTrue($post->tags()->where('slug', 'import')->exists());
            $this->assertTrue($post->tags()->where('slug', 'seo')->exists());

            foreach (app(PostImageService::class)->variantPaths($post->featured_image) as $path) {
                Storage::disk('public')->assertExists($path);
            }
        } finally {
            File::deleteDirectory($workspace);
        }
    }

    public function test_admin_import_uses_default_frontend_fallback_when_featured_image_file_is_missing(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive belum tersedia.');
        }

        $admin = User::factory()->admin()->create();
        $workspace = storage_path('framework/testing/imports/'.Str::uuid());
        File::ensureDirectoryExists($workspace);

        try {
            $zipPath = $workspace.'/posts.zip';
            $this->makeZip($zipPath, [
                'posts' => [
                    [
                        'title' => 'Berita Import Default Missing',
                        'slug' => 'berita-import-default-missing',
                        'excerpt' => 'Ringkasan import.',
                        'content' => '<p>Konten import tanpa gambar.</p>',
                        'category' => 'Nasional',
                        'tags' => ['Import', 'Default'],
                        'status' => PostStatus::Draft->value,
                        'featured_image' => 'images/default.png',
                        'featured_image_alt' => 'Default image',
                    ],
                ],
            ], []);

            $result = app(PostImportService::class)->importFromZip($zipPath, $admin);

            $this->assertSame(1, $result['imported']);
            $this->assertSame(0, $result['failed']);

            $post = Post::query()->where('slug', 'berita-import-default-missing')->firstOrFail();

            $this->assertNull($post->featured_image);
            $this->assertSame('Default image', $post->featured_image_alt);
        } finally {
            File::deleteDirectory($workspace);
        }
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<string, string>  $files
     */
    private function makeZip(string $zipPath, array $manifest, array $files): void
    {
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('posts.json', json_encode($manifest, JSON_THROW_ON_ERROR));

        foreach ($files as $name => $path) {
            $zip->addFile($path, $name);
        }

        $zip->close();
    }

    private function makeImage(string $path, int $width, int $height): void
    {
        $image = imagecreatetruecolor($width, $height);
        $background = imagecolorallocate($image, 30, 102, 255);

        imagefill($image, 0, 0, $background);
        imagejpeg($image, $path, 90);
        imagedestroy($image);
    }
}
