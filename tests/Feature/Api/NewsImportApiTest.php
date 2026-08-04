<?php

namespace Tests\Feature\Api;

use App\Enums\NewsImportStatus;
use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\ImportToken;
use App\Models\NewsImport;
use App\Models\Post;
use App\Models\User;
use App\Services\NewsImportTokenService;
use App\Services\PostImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

class NewsImportApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'news-import.enabled' => true,
            'news-import.rate_limit' => 100,
        ]);
    }

    public function test_import_requires_bearer_token(): void
    {
        $response = $this->withHeader('Accept', 'application/json')
            ->post('/api/import/news');

        $response
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Import token diperlukan.',
            ]);
    }

    public function test_invalid_revoked_expired_and_missing_scope_tokens_are_rejected(): void
    {
        $wrongTokenResponse = $this->withHeaders($this->headers('bin_salah'))
            ->post('/api/import/news');

        $wrongTokenResponse
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Import token tidak valid.',
            ]);

        [$revokedPlain, $revokedToken] = $this->createImportToken(['news:import']);
        $revokedToken->forceFill(['revoked_at' => now()])->save();

        $this->withHeaders($this->headers($revokedPlain))
            ->post('/api/import/news')
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Import token tidak valid.',
            ]);

        [$expiredPlain, $expiredToken] = $this->createImportToken(['news:import']);
        $expiredToken->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->withHeaders($this->headers($expiredPlain))
            ->post('/api/import/news')
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Import token tidak valid.',
            ]);

        [$plainWithoutScope] = $this->createImportToken(['news:publish']);

        $this->withHeaders($this->headers($plainWithoutScope))
            ->post('/api/import/news')
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Token tidak memiliki izin yang diperlukan.',
            ]);
    }

    public function test_draft_import_creates_posts_sources_tags_images_and_audit_log(): void
    {
        $this->skipWithoutImageSupport();
        Storage::fake('public');

        Category::factory()->create(['name' => 'Nasional', 'slug' => 'nasional']);
        [$plainToken, $token, $author] = $this->createImportToken(['news:import']);
        $zipPath = $this->makePackage([
            [
                'title' => 'Berita Ekonomi Digital Hari Ini',
                'slug' => 'berita-ekonomi-digital',
                'excerpt' => 'Ringkasan import dari generator lokal.',
                'content' => '<p>Konten import aman.</p><p><img src="images/content.jpg" alt="Foto konten"></p>',
                'category' => 'Nasional',
                'tags' => ['Import', 'SEO', 'Import', 'Laravel'],
                'status' => 'published',
                'published_at' => '2020-01-01T00:00:00+07:00',
                'author_id' => 999,
                'featured_image' => 'images/featured.jpg',
                'featured_image_alt' => '',
                'detail_images' => ['images/detail-1.jpg', 'images/detail-2.jpg'],
                'seo_title' => 'Berita Ekonomi Digital',
                'seo_description' => 'Ringkasan SEO berita ekonomi digital yang dibuat oleh generator lokal.',
            ],
        ]);

        $response = $this->withHeaders($this->headers($plainToken))
            ->post('/api/import/news', [
                'package' => $this->uploadedZip($zipPath),
                'publish_mode' => 'draft',
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => NewsImportStatus::Completed->value,
                'requested_publish_mode' => 'draft',
                'total' => 1,
                'imported' => 1,
                'failed' => 0,
            ]);

        $post = Post::query()->where('slug', 'berita-ekonomi-digital')->firstOrFail();

        $this->assertSame($author->id, $post->author_id);
        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertNull($post->published_at);
        $this->assertSame('Berita Ekonomi Digital Hari Ini', $post->featured_image_alt);
        $this->assertCount(2, $post->detail_images);
        $this->assertStringContainsString('posts/content', $post->content);
        $this->assertTrue($post->tags()->where('slug', 'import')->exists());
        $this->assertSame(3, $post->tags()->count());
        $this->assertDatabaseHas('news_import_sources', [
            'source_id' => 'SRC-001',
            'publisher' => 'BebasInfo Research',
        ]);
        $this->assertDatabaseHas('news_imports', [
            'import_token_id' => $token->id,
            'status' => NewsImportStatus::Completed->value,
        ]);

        foreach (app(PostImageService::class)->variantPaths($post->featured_image) as $path) {
            Storage::disk('public')->assertExists($path);
        }

        foreach ($post->detail_images as $detailImage) {
            foreach (app(PostImageService::class)->variantPaths($detailImage) as $path) {
                Storage::disk('public')->assertExists($path);
            }
        }
    }

    public function test_import_accepts_article_title_longer_than_eighty_characters(): void
    {
        $this->skipWithoutZipSupport();

        Category::factory()->create(['name' => 'Nasional', 'slug' => 'nasional']);
        [$plainToken] = $this->createImportToken(['news:import']);
        $title = 'Prospek Saham Antam Tetap Menarik Meski Harga Emas Global Sedang Melemah Pekan Ini';

        $zipPath = $this->makePackage([[
            'title' => $title,
            'slug' => 'prospek-saham-antam-tetap-menarik',
            'excerpt' => 'Ringkasan valid untuk import.',
            'content' => '<p>Konten valid untuk judul panjang.</p>',
            'category' => 'Nasional',
            'tags' => ['Import', 'Bisnis'],
            'featured_image' => null,
        ]], includeImages: false);

        $this->withHeaders($this->headers($plainToken))
            ->post('/api/import/news', [
                'package' => $this->uploadedZip($zipPath),
                'publish_mode' => 'draft',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => NewsImportStatus::Completed->value,
                'imported' => 1,
                'failed' => 0,
            ]);

        $this->assertGreaterThan(80, mb_strlen($title));
        $this->assertDatabaseHas('posts', [
            'slug' => 'prospek-saham-antam-tetap-menarik',
            'title' => $title,
        ]);
    }

    public function test_published_import_requires_publish_scope_and_uses_server_time(): void
    {
        $this->skipWithoutImageSupport();

        Category::factory()->create(['name' => 'Nasional', 'slug' => 'nasional']);
        [$plainImportOnly] = $this->createImportToken(['news:import']);
        $zipPath = $this->makePackage();

        $this->withHeaders($this->headers($plainImportOnly))
            ->post('/api/import/news', [
                'package' => $this->uploadedZip($zipPath),
                'publish_mode' => 'published',
            ])
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Token tidak memiliki izin untuk mempublikasikan artikel.',
            ]);

        [$plainPublish] = $this->createImportToken(['news:import', 'news:publish']);
        $zipPath = $this->makePackage();
        $beforeImport = now()->subSecond();

        $this->withHeaders($this->headers($plainPublish))
            ->post('/api/import/news', [
                'package' => $this->uploadedZip($zipPath),
                'publish_mode' => 'published',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => NewsImportStatus::Completed->value,
            ]);

        $post = Post::query()->where('slug', 'contoh-import-api')->firstOrFail();

        $this->assertSame(PostStatus::Published, $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertTrue($post->published_at->greaterThanOrEqualTo($beforeImport));
        $this->assertNotSame('2020-01-01 00:00:00', $post->published_at->format('Y-m-d H:i:s'));
    }

    public function test_missing_featured_image_uses_default_frontend_fallback_without_failing(): void
    {
        $this->skipWithoutZipSupport();

        Category::factory()->create(['name' => 'Nasional', 'slug' => 'nasional']);
        [$plainToken] = $this->createImportToken(['news:import']);
        $zipPath = $this->makePackage([
            [
                'title' => 'Berita Tanpa Gambar',
                'slug' => 'berita-tanpa-gambar',
                'excerpt' => 'Ringkasan.',
                'content' => '<p>Konten tetap valid.</p>',
                'category' => 'Nasional',
                'tags' => ['Default', 'Image', 'Import'],
                'featured_image' => null,
            ],
        ], includeImages: false);

        $this->withHeaders($this->headers($plainToken))
            ->post('/api/import/news', [
                'package' => $this->uploadedZip($zipPath),
                'publish_mode' => 'draft',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'imported' => 1,
                'failed' => 0,
            ]);

        $post = Post::query()->where('slug', 'berita-tanpa-gambar')->firstOrFail();

        $this->assertNull($post->featured_image);
        $this->assertDatabaseHas('news_import_items', [
            'slug' => 'berita-tanpa-gambar',
            'image_path' => null,
        ]);
    }

    public function test_missing_featured_image_file_uses_default_frontend_fallback_without_failing(): void
    {
        $this->skipWithoutZipSupport();

        Category::factory()->create(['name' => 'Nasional', 'slug' => 'nasional']);
        [$plainToken] = $this->createImportToken(['news:import']);
        $zipPath = $this->makePackage([
            [
                'title' => 'Berita Default Missing File',
                'slug' => 'berita-default-missing-file',
                'excerpt' => 'Ringkasan.',
                'content' => '<p>Konten tetap valid.</p>',
                'category' => 'Nasional',
                'tags' => ['Default', 'Image', 'Missing'],
                'featured_image' => 'images/default.png',
                'featured_image_alt' => 'Default image',
            ],
        ], includeImages: false);

        $this->withHeaders($this->headers($plainToken))
            ->post('/api/import/news', [
                'package' => $this->uploadedZip($zipPath),
                'publish_mode' => 'draft',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'imported' => 1,
                'failed' => 0,
            ]);

        $post = Post::query()->where('slug', 'berita-default-missing-file')->firstOrFail();

        $this->assertNull($post->featured_image);
        $this->assertSame('Default image', $post->featured_image_alt);
    }

    public function test_zip_security_rejects_path_traversal_and_disallowed_files(): void
    {
        $this->skipWithoutZipSupport();

        [$plainToken] = $this->createImportToken(['news:import']);
        $zipPath = $this->makeUnsafePackage('../evil.php');

        $this->withHeaders($this->headers($plainToken))
            ->post('/api/import/news', [
                'package' => $this->uploadedZip($zipPath),
                'publish_mode' => 'draft',
            ])
            ->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'status' => NewsImportStatus::Failed->value,
            ]);

        $this->assertDatabaseCount('posts', 0);

        $zipPath = $this->makeUnsafePackage('images/nested.zip');

        $this->withHeaders($this->headers($plainToken))
            ->post('/api/import/news', [
                'package' => $this->uploadedZip($zipPath),
                'publish_mode' => 'draft',
            ])
            ->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'status' => NewsImportStatus::Failed->value,
            ]);
    }

    public function test_one_invalid_article_does_not_rollback_the_whole_batch(): void
    {
        $this->skipWithoutZipSupport();

        Category::factory()->create(['name' => 'Nasional', 'slug' => 'nasional']);
        [$plainToken] = $this->createImportToken(['news:import']);
        $zipPath = $this->makePackage([
            [
                'title' => 'Artikel Valid Batch',
                'slug' => 'artikel-valid-batch',
                'excerpt' => 'Ringkasan valid.',
                'content' => '<p>Konten valid.</p>',
                'category' => 'Nasional',
                'tags' => ['Import', 'Batch', 'Valid'],
                'featured_image' => null,
            ],
            [
                'title' => 'Artikel Kategori Hilang',
                'slug' => 'artikel-kategori-hilang',
                'excerpt' => 'Ringkasan gagal.',
                'content' => '<p>Konten valid.</p>',
                'category' => 'Kategori Tidak Ada',
                'tags' => ['Import'],
                'featured_image' => null,
            ],
        ], includeImages: false);

        $this->withHeaders($this->headers($plainToken))
            ->post('/api/import/news', [
                'package' => $this->uploadedZip($zipPath),
                'publish_mode' => 'draft',
            ])
            ->assertStatus(207)
            ->assertJson([
                'success' => true,
                'status' => NewsImportStatus::CompletedWithErrors->value,
                'imported' => 1,
                'failed' => 1,
            ]);

        $this->assertDatabaseHas('posts', ['slug' => 'artikel-valid-batch']);
        $this->assertDatabaseMissing('posts', ['slug' => 'artikel-kategori-hilang']);
        $this->assertDatabaseHas('news_import_items', ['slug' => 'artikel-kategori-hilang']);
    }

    public function test_idempotency_key_prevents_duplicate_posts(): void
    {
        $this->skipWithoutZipSupport();

        Category::factory()->create(['name' => 'Nasional', 'slug' => 'nasional']);
        [$plainToken] = $this->createImportToken(['news:import']);
        $idempotencyKey = (string) Str::uuid();

        $firstResponse = $this->withHeaders($this->headers($plainToken, $idempotencyKey))
            ->post('/api/import/news', [
                'package' => $this->uploadedZip($this->makePackage(includeImages: false)),
                'publish_mode' => 'draft',
            ]);

        $firstResponse->assertOk();
        $firstImportId = $firstResponse->json('import_id');

        $secondResponse = $this->withHeaders($this->headers($plainToken, $idempotencyKey))
            ->post('/api/import/news', [
                'package' => $this->uploadedZip($this->makePackage(includeImages: false)),
                'publish_mode' => 'draft',
            ]);

        $secondResponse
            ->assertOk()
            ->assertJson([
                'success' => true,
                'import_id' => $firstImportId,
                'imported' => 1,
            ]);

        $this->assertSame(1, Post::query()->where('slug', 'contoh-import-api')->count());
        $this->assertSame(1, NewsImport::query()->where('idempotency_key', $idempotencyKey)->count());
    }

    public function test_status_endpoint_is_limited_to_the_import_token_that_created_it(): void
    {
        $this->skipWithoutZipSupport();

        Category::factory()->create(['name' => 'Nasional', 'slug' => 'nasional']);
        [$plainToken] = $this->createImportToken(['news:import']);
        [$otherPlainToken] = $this->createImportToken(['news:import']);

        $importResponse = $this->withHeaders($this->headers($plainToken))
            ->post('/api/import/news', [
                'package' => $this->uploadedZip($this->makePackage(includeImages: false)),
                'publish_mode' => 'draft',
            ]);

        $importResponse->assertOk();

        $this->withHeaders($this->headers($plainToken))
            ->get('/api/import/news/'.$importResponse->json('import_id'))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => NewsImportStatus::Completed->value,
            ]);

        $this->withHeaders($this->headers($otherPlainToken))
            ->get('/api/import/news/'.$importResponse->json('import_id'))
            ->assertForbidden();
    }

    public function test_cleanup_command_removes_old_temporary_files_and_old_audit_logs(): void
    {
        $this->skipWithoutZipSupport();

        $oldDirectory = storage_path('app/private/imports/api/uploads');
        File::ensureDirectoryExists($oldDirectory);
        $oldFile = $oldDirectory.'/old.zip';
        File::put($oldFile, 'old');
        touch($oldFile, now()->subDays(2)->getTimestamp());

        $oldImport = NewsImport::query()->create([
            'uuid' => (string) Str::uuid(),
            'original_filename' => 'old.zip',
            'requested_publish_mode' => 'draft',
            'status' => NewsImportStatus::Completed,
        ]);
        $oldImport->forceFill([
            'created_at' => now()->subDays(120),
            'updated_at' => now()->subDays(120),
        ])->save();

        $this->artisan('news-imports:cleanup', ['--hours' => 24])
            ->assertSuccessful();

        $this->assertFileDoesNotExist($oldFile);
        $this->assertDatabaseCount('news_imports', 0);
    }

    /**
     * @return array{0: string, 1: ImportToken, 2: User}
     */
    private function createImportToken(array $abilities): array
    {
        $admin = User::factory()->admin()->create();

        $result = app(NewsImportTokenService::class)->create(
            name: 'Token Import Test',
            creator: $admin,
            user: $admin,
            abilities: $abilities,
            expiresAt: now()->addDay(),
        );

        return [$result['plain_text_token'], $result['token'], $admin];
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $plainToken, ?string $idempotencyKey = null): array
    {
        return array_filter([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$plainToken,
            'Idempotency-Key' => $idempotencyKey,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $posts
     */
    private function makePackage(?array $posts = null, bool $includeImages = true): string
    {
        $this->skipWithoutZipSupport();

        $workspace = storage_path('framework/testing/news-import/'.Str::uuid());
        File::ensureDirectoryExists($workspace);

        if ($includeImages) {
            $this->makeImage($workspace.'/featured.jpg', 1600, 900);
            $this->makeImage($workspace.'/content.jpg', 1200, 675);
            $this->makeImage($workspace.'/detail-1.jpg', 1600, 900);
            $this->makeImage($workspace.'/detail-2.jpg', 1600, 900);
        }

        $posts ??= [[
            'title' => 'Contoh Import API',
            'slug' => 'contoh-import-api',
            'excerpt' => 'Ringkasan contoh import.',
            'content' => '<p>Konten import dari generator lokal.</p>',
            'category' => 'Nasional',
            'tags' => ['Import', 'Laravel', 'Berita'],
            'status' => 'published',
            'published_at' => '2020-01-01T00:00:00+07:00',
            'featured_image' => $includeImages ? 'images/featured.jpg' : null,
            'featured_image_alt' => 'Gambar import',
            'seo_title' => 'Contoh Import API',
            'seo_description' => 'Deskripsi SEO untuk contoh import API BebasInfo.',
        ]];

        $sources = [
            'generated_at' => now()->toIso8601String(),
            'sources' => [
                [
                    'id' => 'SRC-001',
                    'requested_url' => 'https://example.com/requested',
                    'final_url' => 'https://example.com/final',
                    'publisher' => 'BebasInfo Research',
                    'title' => 'Sumber Contoh',
                    'author' => 'Reporter',
                    'published_at' => '2026-07-31T09:00:00+07:00',
                    'retrieved_at' => now()->toIso8601String(),
                    'sha256' => hash('sha256', 'source'),
                ],
            ],
            'post_sources' => array_map(fn (array $post): array => [
                'slug' => (string) $post['slug'],
                'source_ids' => ['SRC-001'],
            ], $posts),
        ];

        $manifest = [
            'format' => 'bebasinfo-news-import',
            'version' => '1.0',
            'content_mode' => 'ai',
            'image_mode' => $includeImages ? 'generated' : 'default',
            'requested_publish_mode' => 'draft',
            'post_count' => count($posts),
            'source_count' => count($sources['sources']),
            'generated_at' => now()->toIso8601String(),
            'files' => [
                ['path' => 'posts.json', 'sha256' => '', 'size' => 0],
                ['path' => 'sources.json', 'sha256' => '', 'size' => 0],
            ],
        ];

        $zipPath = $workspace.'/bebasinfo-import.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR));
        $zip->addFromString('posts.json', json_encode(['posts' => $posts], JSON_THROW_ON_ERROR));
        $zip->addFromString('sources.json', json_encode($sources, JSON_THROW_ON_ERROR));

        if ($includeImages) {
            $zip->addFile($workspace.'/featured.jpg', 'images/featured.jpg');
            $zip->addFile($workspace.'/content.jpg', 'images/content.jpg');
            $zip->addFile($workspace.'/detail-1.jpg', 'images/detail-1.jpg');
            $zip->addFile($workspace.'/detail-2.jpg', 'images/detail-2.jpg');
        }

        $zip->close();

        return $zipPath;
    }

    private function makeUnsafePackage(string $unsafePath): string
    {
        $workspace = storage_path('framework/testing/news-import/'.Str::uuid());
        File::ensureDirectoryExists($workspace);

        $zipPath = $workspace.'/unsafe.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('manifest.json', json_encode([
            'format' => 'bebasinfo-news-import',
            'version' => '1.0',
            'post_count' => 0,
            'source_count' => 0,
        ], JSON_THROW_ON_ERROR));
        $zip->addFromString('posts.json', json_encode(['posts' => []], JSON_THROW_ON_ERROR));
        $zip->addFromString('sources.json', json_encode(['sources' => []], JSON_THROW_ON_ERROR));
        $zip->addFromString($unsafePath, 'bad');
        $zip->close();

        return $zipPath;
    }

    private function uploadedZip(string $zipPath): UploadedFile
    {
        return new UploadedFile($zipPath, 'bebasinfo-import.zip', 'application/zip', null, true);
    }

    private function makeImage(string $path, int $width, int $height): void
    {
        $image = imagecreatetruecolor($width, $height);
        $background = imagecolorallocate($image, 30, 102, 255);

        imagefill($image, 0, 0, $background);
        imagejpeg($image, $path, 90);
        imagedestroy($image);
    }

    private function skipWithoutZipSupport(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive belum tersedia.');
        }
    }

    private function skipWithoutImageSupport(): void
    {
        $this->skipWithoutZipSupport();

        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD belum tersedia.');
        }
    }
}
