<?php

namespace Tests\Feature\Filament;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PostResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_editor_can_open_post_list_but_inactive_user_cannot(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();
        $inactive = User::factory()->editor()->inactive()->create();

        $this->actingAs($admin)->get(PostResource::getUrl('index'))->assertOk();
        $this->actingAs($editor)->get(PostResource::getUrl('index'))->assertOk();
        $this->actingAs($inactive)->get(PostResource::getUrl('index'))->assertForbidden();
    }

    public function test_editor_list_only_shows_owned_posts(): void
    {
        $editor = User::factory()->editor()->create();
        $otherEditor = User::factory()->editor()->create();
        $ownedPost = Post::factory()->create(['author_id' => $editor->id]);
        $otherPost = Post::factory()->create(['author_id' => $otherEditor->id]);

        $this->actingAs($editor);

        Livewire::test(ListPosts::class)
            ->assertCanSeeTableRecords(collect([$ownedPost]))
            ->assertCanNotSeeTableRecords(collect([$otherPost]));
    }

    public function test_admin_can_view_any_post_and_editor_cannot_open_other_editor_post_urls(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();
        $otherPost = Post::factory()->create();

        $this->actingAs($admin)->get(PostResource::getUrl('view', ['record' => $otherPost]))->assertOk();

        $this->actingAs($editor);
        $this->get(PostResource::getUrl('view', ['record' => $otherPost]))->assertNotFound();
        $this->get(PostResource::getUrl('edit', ['record' => $otherPost]))->assertNotFound();
    }

    public function test_admin_can_create_post_with_tags_as_draft(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->editor()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $this->actingAs($admin);

        Livewire::test(CreatePost::class)
            ->fillForm([
                'author_id' => $author->id,
                'category_id' => $category->id,
                'tags' => [$tag->id],
                'title' => 'Judul Berita Nasional',
                'content' => 'Isi berita nasional yang layak untuk disimpan.',
                'is_featured' => true,
                'robots_index' => true,
                'robots_follow' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();

        $post = Post::query()->where('slug', 'judul-berita-nasional')->firstOrFail();

        $this->assertSame($author->id, $post->author_id);
        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertNull($post->published_at);
        $this->assertTrue($post->is_featured);
        $this->assertTrue($post->tags()->whereKey($tag->id)->exists());
    }

    public function test_editor_create_forces_administrative_fields(): void
    {
        $editor = User::factory()->editor()->create();
        $otherEditor = User::factory()->editor()->create();
        $category = Category::factory()->create();

        $this->actingAs($editor);

        Livewire::test(CreatePost::class)
            ->fillForm([
                'author_id' => $otherEditor->id,
                'editor_id' => $otherEditor->id,
                'category_id' => $category->id,
                'title' => 'Berita Editor Baru',
                'slug' => 'berita-editor-baru',
                'content' => 'Isi berita editor.',
                'status' => PostStatus::Published->value,
                'published_at' => now()->subDay(),
                'is_featured' => true,
                'robots_index' => false,
                'robots_follow' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $post = Post::query()->where('slug', 'berita-editor-baru')->firstOrFail();

        $this->assertSame($editor->id, $post->author_id);
        $this->assertNull($post->editor_id);
        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertNull($post->published_at);
        $this->assertFalse($post->is_featured);
        $this->assertTrue($post->robots_index);
        $this->assertTrue($post->robots_follow);
    }

    public function test_required_fields_and_unique_slug_are_validated(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        Post::factory()->create(['slug' => 'slug-sama']);

        $this->actingAs($admin);

        Livewire::test(CreatePost::class)
            ->fillForm([
                'category_id' => $category->id,
                'slug' => 'slug-sama',
                'content' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'title' => 'required',
                'slug' => 'unique',
                'content',
            ]);
    }

    public function test_editor_can_edit_owned_draft_and_review_but_not_published_or_scheduled(): void
    {
        $editor = User::factory()->editor()->create();
        $draft = Post::factory()->draft()->create(['author_id' => $editor->id]);
        $review = Post::factory()->review()->create(['author_id' => $editor->id]);
        $published = Post::factory()->published()->create(['author_id' => $editor->id]);
        $scheduled = Post::factory()->scheduled()->create(['author_id' => $editor->id]);

        $this->actingAs($editor);

        $this->get(PostResource::getUrl('edit', ['record' => $draft]))->assertOk();
        $this->get(PostResource::getUrl('edit', ['record' => $review]))->assertOk();
        $this->get(PostResource::getUrl('edit', ['record' => $published]))->assertForbidden();
        $this->get(PostResource::getUrl('edit', ['record' => $scheduled]))->assertForbidden();
    }

    public function test_editor_update_cannot_change_administrative_fields_and_can_update_tags(): void
    {
        $editor = User::factory()->editor()->create();
        $otherEditor = User::factory()->editor()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $post = Post::factory()->draft()->create([
            'author_id' => $editor->id,
            'editor_id' => null,
            'category_id' => $category->id,
            'is_featured' => false,
            'robots_index' => true,
            'robots_follow' => true,
        ]);

        $this->actingAs($editor);

        Livewire::test(EditPost::class, ['record' => $post->id])
            ->fillForm([
                'author_id' => $otherEditor->id,
                'editor_id' => $otherEditor->id,
                'category_id' => $category->id,
                'tags' => [$tag->id],
                'title' => 'Judul Diubah',
                'slug' => 'judul-diubah',
                'content' => 'Konten diubah.',
                'status' => PostStatus::Published->value,
                'published_at' => now()->subDay(),
                'is_featured' => true,
                'robots_index' => false,
                'robots_follow' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $post->refresh();

        $this->assertSame($editor->id, $post->author_id);
        $this->assertNull($post->editor_id);
        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertNull($post->published_at);
        $this->assertFalse($post->is_featured);
        $this->assertTrue($post->robots_index);
        $this->assertTrue($post->robots_follow);
        $this->assertTrue($post->tags()->whereKey($tag->id)->exists());
    }

    public function test_editor_workflow_is_limited_to_draft_and_review(): void
    {
        $editor = User::factory()->editor()->create();
        $draft = Post::factory()->draft()->create(['author_id' => $editor->id]);
        $review = Post::factory()->review()->create(['author_id' => $editor->id]);

        $this->actingAs($editor);

        Livewire::test(ListPosts::class)
            ->callTableAction('submit_for_review', $draft)
            ->assertNotified();

        $this->assertSame(PostStatus::Review, $draft->fresh()->status);

        Livewire::test(ListPosts::class)
            ->callTableAction('return_to_draft', $review)
            ->assertNotified();

        $this->assertSame(PostStatus::Draft, $review->fresh()->status);

        Livewire::test(ListPosts::class)
            ->assertTableActionHidden('publish', $review->fresh())
            ->assertTableActionHidden('schedule', $review->fresh())
            ->assertTableActionHidden('archive', $review->fresh());
    }

    public function test_admin_workflow_schedule_publish_cancel_archive_and_restore_to_draft(): void
    {
        $admin = User::factory()->admin()->create();
        $review = Post::factory()->review()->create();
        $publishedAt = now()->addDay()->format('Y-m-d H:i');

        $this->actingAs($admin);

        Livewire::test(EditPost::class, ['record' => $review->id])
            ->callAction('schedule', ['published_at' => $publishedAt])
            ->assertNotified();

        $review->refresh();

        $this->assertSame(PostStatus::Scheduled, $review->status);
        $this->assertNotNull($review->published_at);
        $this->assertSame($admin->id, $review->editor_id);

        Livewire::test(EditPost::class, ['record' => $review->id])
            ->callAction('return_to_draft')
            ->assertNotified();

        $this->assertSame(PostStatus::Draft, $review->fresh()->status);
        $this->assertNull($review->fresh()->published_at);

        $review->update(['status' => PostStatus::Review]);

        Livewire::test(EditPost::class, ['record' => $review->id])
            ->callAction('publish')
            ->assertNotified();

        $review->refresh();

        $this->assertSame(PostStatus::Published, $review->status);
        $this->assertNotNull($review->published_at);

        Livewire::test(EditPost::class, ['record' => $review->id])
            ->callAction('archive')
            ->assertNotified();

        $archivedPublishedAt = $review->fresh()->published_at;

        $this->assertSame(PostStatus::Archived, $review->fresh()->status);
        $this->assertTrue($review->fresh()->published_at->equalTo($archivedPublishedAt));

        Livewire::test(EditPost::class, ['record' => $review->id])
            ->callAction('return_to_draft')
            ->assertNotified();

        $this->assertSame(PostStatus::Draft, $review->fresh()->status);
        $this->assertNull($review->fresh()->published_at);
    }

    public function test_schedule_rejects_past_time_from_filament_action(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->review()->create();

        $this->actingAs($admin);

        Livewire::test(EditPost::class, ['record' => $post->id])
            ->callAction('schedule', ['published_at' => now()->subHour()->format('Y-m-d H:i')])
            ->assertActionHalted();

        $this->assertSame(PostStatus::Review, $post->fresh()->status);
        $this->assertNull($post->fresh()->published_at);
    }

    public function test_admin_can_soft_delete_restore_and_force_delete_post(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EditPost::class, ['record' => $post->id])
            ->callAction(DeleteAction::class)
            ->assertNotified();

        $this->assertSoftDeleted($post);

        Livewire::test(EditPost::class, ['record' => $post->id])
            ->callAction(RestoreAction::class)
            ->assertNotified();

        $this->assertNotSoftDeleted($post->fresh());

        $post->delete();

        Livewire::test(EditPost::class, ['record' => $post->id])
            ->callAction(ForceDeleteAction::class)
            ->assertNotified();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_table_filters_search_sort_and_status_badge_work(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Ekonomi']);
        $target = Post::factory()->review()->featured()->create([
            'author_id' => $admin->id,
            'category_id' => $category->id,
            'title' => 'Pertumbuhan Ekonomi Digital',
            'slug' => 'pertumbuhan-ekonomi-digital',
            'created_at' => now()->subDay(),
        ]);
        $other = Post::factory()->draft()->create(['title' => 'Olahraga Sore']);

        $this->actingAs($admin);

        Livewire::test(ListPosts::class)
            ->assertTableColumnStateSet('status', PostStatus::Review, $target)
            ->filterTable('status', PostStatus::Review->value)
            ->filterTable('category', $category->id)
            ->filterTable('is_featured', true)
            ->assertCanSeeTableRecords(collect([$target]))
            ->assertCanNotSeeTableRecords(collect([$other]))
            ->resetTableFilters()
            ->searchTable('ekonomi-digital')
            ->assertCanSeeTableRecords(collect([$target]))
            ->assertCanNotSeeTableRecords(collect([$other]))
            ->sortTable('created_at', 'desc');
    }
}
