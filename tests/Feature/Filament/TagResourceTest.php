<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Tags\Pages\CreateTag;
use App\Filament\Resources\Tags\Pages\EditTag;
use App\Filament\Resources\Tags\Pages\ListTags;
use App\Filament\Resources\Tags\TagResource;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TagResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_editor_can_open_tag_list(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();

        $this->actingAs($admin)->get(TagResource::getUrl('index'))->assertOk();
        $this->actingAs($editor)->get(TagResource::getUrl('index'))->assertOk();
    }

    public function test_admin_can_create_tag(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(CreateTag::class)
            ->fillForm([
                'name' => 'Breaking News',
                'description' => 'Tag berita cepat.',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();

        $this->assertDatabaseHas(Tag::class, [
            'name' => 'Breaking News',
            'slug' => 'breaking-news',
        ]);
    }

    public function test_editor_cannot_create_or_edit_tag(): void
    {
        $editor = User::factory()->editor()->create();
        $tag = Tag::factory()->create();

        $this->actingAs($editor);

        $this->get(TagResource::getUrl('create'))->assertForbidden();
        $this->get(TagResource::getUrl('edit', ['record' => $tag]))->assertForbidden();
    }

    public function test_tag_slug_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        Tag::factory()->create(['slug' => 'breaking']);

        $this->actingAs($admin);

        Livewire::test(CreateTag::class)
            ->fillForm([
                'name' => 'Breaking Lain',
                'slug' => 'breaking',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    public function test_admin_can_edit_tag(): void
    {
        $admin = User::factory()->admin()->create();
        $tag = Tag::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EditTag::class, ['record' => $tag->id])
            ->fillForm([
                'name' => 'Tag Update',
                'slug' => 'tag-update',
                'description' => 'Deskripsi baru',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas(Tag::class, [
            'id' => $tag->id,
            'name' => 'Tag Update',
            'slug' => 'tag-update',
        ]);
    }

    public function test_admin_can_delete_tag_without_deleting_posts_and_pivot_is_removed(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();
        $tag = Tag::factory()->create();
        $post->tags()->attach($tag->id);

        $this->actingAs($admin);

        Livewire::test(EditTag::class, ['record' => $tag->id])
            ->callAction(DeleteAction::class)
            ->assertNotified();

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
        ]);
        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
        $this->assertDatabaseMissing('post_tag', [
            'post_id' => $post->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_tag_posts_count_and_search_work(): void
    {
        $admin = User::factory()->admin()->create();
        $target = Tag::factory()->create(['name' => 'Ekonomi Digital', 'slug' => 'ekonomi-digital']);
        $other = Tag::factory()->create(['name' => 'Olahraga', 'slug' => 'olahraga']);
        $post = Post::factory()->create();
        $post->tags()->attach($target->id);

        $this->actingAs($admin);

        Livewire::test(ListTags::class)
            ->assertTableColumnStateSet('posts_count', 1, $target)
            ->searchTable('Ekonomi')
            ->assertCanSeeTableRecords(collect([$target]))
            ->assertCanNotSeeTableRecords(collect([$other]));
    }
}
