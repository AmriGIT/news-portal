<?php

namespace Tests\Feature\Filament;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\PostImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PostFeaturedImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_featured_image_when_creating_post(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin);

        Livewire::test(CreatePost::class)
            ->fillForm([
                'category_id' => $category->id,
                'title' => 'Berita Dengan Gambar',
                'slug' => 'berita-dengan-gambar',
                'content' => '<p>Isi berita dengan gambar utama.</p>',
                'featured_image' => UploadedFile::fake()->image('nama-asli.jpg', 1800, 1200)->size(1000),
                'featured_image_alt' => 'Keramaian warga di ruang publik',
                'robots_index' => true,
                'robots_follow' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $post = Post::query()->where('slug', 'berita-dengan-gambar')->firstOrFail();

        $this->assertNotNull($post->featured_image);
        $this->assertStringEndsWith('.webp', $post->featured_image);
        $this->assertStringNotContainsString('nama-asli', $post->featured_image);

        foreach (app(PostImageService::class)->variantPaths($post->featured_image) as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_editor_can_upload_featured_image_on_owned_draft_and_review_posts(): void
    {
        Storage::fake('public');
        $editor = User::factory()->editor()->create();
        $draft = Post::factory()->draft()->create(['author_id' => $editor->id]);
        $review = Post::factory()->review()->create(['author_id' => $editor->id]);

        $this->actingAs($editor);

        foreach ([$draft, $review] as $post) {
            Livewire::test(EditPost::class, ['record' => $post->id])
                ->fillForm([
                    'category_id' => $post->category_id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'content' => '<p>Isi berita milik editor.</p>',
                    'featured_image' => UploadedFile::fake()->image('editor.jpg', 1800, 1200)->size(1000),
                    'featured_image_alt' => 'Aktivitas narasumber berita',
                ])
                ->call('save')
                ->assertHasNoFormErrors()
                ->assertNotified();

            $this->assertNotNull($post->fresh()->featured_image);
        }
    }

    public function test_alt_text_is_required_when_featured_image_exists(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin);

        Livewire::test(CreatePost::class)
            ->fillForm([
                'category_id' => $category->id,
                'title' => 'Gambar Tanpa Alt',
                'slug' => 'gambar-tanpa-alt',
                'content' => '<p>Isi berita.</p>',
                'featured_image' => UploadedFile::fake()->image('image.jpg', 1800, 1200)->size(1000),
                'featured_image_alt' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['featured_image_alt' => 'required']);
    }

    public function test_replace_featured_image_deletes_old_variants_after_update(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $oldPaths = app(PostImageService::class)->storeFeaturedImage(UploadedFile::fake()->image('old.jpg', 1800, 1200)->size(1000));
        $post = Post::factory()->draft()->create([
            'featured_image' => $oldPaths['original'],
            'featured_image_alt' => 'Foto lama',
        ]);

        $this->actingAs($admin);

        Livewire::test(EditPost::class, ['record' => $post->id])
            ->fillForm([
                'featured_image' => null,
            ])
            ->fillForm([
                'category_id' => $post->category_id,
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => '<p>Isi berita.</p>',
                'featured_image' => [UploadedFile::fake()->image('new.jpg', 1800, 1200)->size(1000)],
                'featured_image_alt' => 'Foto baru',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        foreach ($oldPaths as $path) {
            Storage::disk('public')->assertMissing($path);
        }

        foreach (app(PostImageService::class)->variantPaths($post->fresh()->featured_image) as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_soft_delete_keeps_image_and_force_delete_removes_it(): void
    {
        Storage::fake('public');
        $paths = app(PostImageService::class)->storeFeaturedImage(UploadedFile::fake()->image('image.jpg', 1800, 1200)->size(1000));
        $post = Post::factory()->published()->create(['featured_image' => $paths['original']]);

        $post->delete();

        foreach ($paths as $path) {
            Storage::disk('public')->assertExists($path);
        }

        $post->forceDelete();

        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_admin_post_view_shows_featured_image_preview(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('posts/featured-preview.webp', 'preview');

        $admin = User::factory()->admin()->create();
        $post = Post::factory()->published()->create([
            'featured_image' => 'posts/featured-preview.webp',
            'featured_image_alt' => 'Preview gambar berita',
        ]);

        $this->actingAs($admin)
            ->get(PostResource::getUrl('view', ['record' => $post]))
            ->assertOk()
            ->assertSee('posts/featured-preview.webp')
            ->assertSee('Preview gambar berita');
    }

    public function test_editor_cannot_upload_featured_image_on_published_post(): void
    {
        $editor = User::factory()->editor()->create();
        $post = Post::factory()->published()->create([
            'author_id' => $editor->id,
            'status' => PostStatus::Published,
        ]);

        $this->actingAs($editor)
            ->get(PostResource::getUrl('edit', ['record' => $post]))
            ->assertForbidden();
    }
}
