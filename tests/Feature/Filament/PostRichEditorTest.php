<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PostRichEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_sanitized_rich_text_content(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin);

        Livewire::test(CreatePost::class)
            ->fillForm([
                'category_id' => $category->id,
                'title' => 'Konten Rich Text',
                'slug' => 'konten-rich-text',
                'content' => '<h2>Subjudul</h2><p onclick="alert(1)">Isi <strong>penting</strong><script>alert(1)</script><a href="javascript:alert(1)">buruk</a></p><blockquote>Kutipan</blockquote>',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $content = Post::query()->where('slug', 'konten-rich-text')->value('content');

        $this->assertStringContainsString('<h2>Subjudul</h2>', $content);
        $this->assertStringContainsString('<strong>penting</strong>', $content);
        $this->assertStringContainsString('<blockquote>Kutipan</blockquote>', $content);
        $this->assertStringNotContainsString('script', $content);
        $this->assertStringNotContainsString('onclick', $content);
        $this->assertStringNotContainsString('javascript:', $content);
    }

    public function test_empty_rich_text_content_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin);

        Livewire::test(CreatePost::class)
            ->fillForm([
                'category_id' => $category->id,
                'title' => 'Konten Kosong',
                'slug' => 'konten-kosong',
                'content' => '<p><br></p>',
            ])
            ->call('create')
            ->assertHasFormErrors(['content']);
    }

    public function test_editor_can_update_owned_rich_text_content(): void
    {
        $editor = User::factory()->editor()->create();
        $post = Post::factory()->draft()->create(['author_id' => $editor->id]);

        $this->actingAs($editor);

        Livewire::test(EditPost::class, ['record' => $post->id])
            ->fillForm([
                'category_id' => $post->category_id,
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => '<p>Konten editor yang diperbarui.</p>',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertStringContainsString('Konten editor yang diperbarui.', $post->fresh()->content);
    }
}
