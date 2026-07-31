<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostRedirect;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_role_is_cast_to_user_role_enum(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertSame(UserRole::Admin, $user->role);
    }

    public function test_post_status_is_cast_to_post_status_enum(): void
    {
        $post = Post::factory()->published()->create();

        $this->assertSame(PostStatus::Published, $post->status);
    }

    public function test_post_relations_work(): void
    {
        $author = User::factory()->editor()->create();
        $editor = User::factory()->admin()->create();
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(3)->create();
        $post = Post::factory()->create([
            'author_id' => $author->id,
            'editor_id' => $editor->id,
            'category_id' => $category->id,
        ]);
        $post->tags()->attach($tags->pluck('id'));
        $redirect = PostRedirect::factory()->create([
            'post_id' => $post->id,
        ]);

        $this->assertTrue($post->author->is($author));
        $this->assertTrue($post->editor->is($editor));
        $this->assertTrue($post->category->is($category));
        $this->assertCount(3, $post->tags);
        $this->assertTrue($category->posts->first()->is($post));
        $this->assertTrue($tags->first()->posts->first()->is($post));
        $this->assertTrue($post->redirects->first()->is($redirect));
    }

    public function test_post_editor_is_nullable(): void
    {
        $post = Post::factory()->create([
            'editor_id' => null,
        ]);

        $this->assertNull($post->editor_id);
        $this->assertNull($post->editor);
    }

    public function test_published_scope_only_returns_posts_that_can_be_displayed(): void
    {
        $published = Post::factory()->published()->create();
        $futurePublished = Post::factory()->create([
            'status' => PostStatus::Published,
            'published_at' => now()->addDay(),
        ]);
        $draft = Post::factory()->draft()->create();
        $scheduled = Post::factory()->scheduled()->create();

        $ids = Post::query()->published()->pluck('id')->all();

        $this->assertContains($published->id, $ids);
        $this->assertNotContains($futurePublished->id, $ids);
        $this->assertNotContains($draft->id, $ids);
        $this->assertNotContains($scheduled->id, $ids);
    }

    public function test_scheduled_scope_only_returns_future_scheduled_posts(): void
    {
        $scheduled = Post::factory()->scheduled()->create();
        $pastScheduled = Post::factory()->create([
            'status' => PostStatus::Scheduled,
            'published_at' => now()->subDay(),
        ]);
        $published = Post::factory()->published()->create();

        $ids = Post::query()->scheduled()->pluck('id')->all();

        $this->assertContains($scheduled->id, $ids);
        $this->assertNotContains($pastScheduled->id, $ids);
        $this->assertNotContains($published->id, $ids);
    }

    public function test_featured_scope_only_returns_featured_posts(): void
    {
        $featured = Post::factory()->featured()->create();
        $normal = Post::factory()->create();

        $ids = Post::query()->featured()->pluck('id')->all();

        $this->assertContains($featured->id, $ids);
        $this->assertNotContains($normal->id, $ids);
    }

    public function test_slug_constraints_are_unique(): void
    {
        Post::factory()->create(['slug' => 'same-post-slug']);
        Category::factory()->create(['slug' => 'same-category-slug']);
        Tag::factory()->create(['slug' => 'same-tag-slug']);
        PostRedirect::factory()->create(['old_slug' => 'same-old-slug']);

        $this->assertThrows(
            fn () => Post::factory()->create(['slug' => 'same-post-slug']),
            QueryException::class,
        );
        $this->assertThrows(
            fn () => Category::factory()->create(['slug' => 'same-category-slug']),
            QueryException::class,
        );
        $this->assertThrows(
            fn () => Tag::factory()->create(['slug' => 'same-tag-slug']),
            QueryException::class,
        );
        $this->assertThrows(
            fn () => PostRedirect::factory()->create(['old_slug' => 'same-old-slug']),
            QueryException::class,
        );
    }

    public function test_post_soft_delete_works(): void
    {
        $post = Post::factory()->create();

        $post->delete();

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
        $this->assertNotNull(Post::withTrashed()->find($post->id));
    }

    public function test_author_cannot_be_deleted_when_posts_still_exist(): void
    {
        $author = User::factory()->editor()->create();
        Post::factory()->create([
            'author_id' => $author->id,
        ]);

        $this->expectException(QueryException::class);

        $author->delete();
    }

    public function test_editor_is_set_to_null_when_editor_is_deleted(): void
    {
        $editor = User::factory()->editor()->create();
        $post = Post::factory()->create([
            'editor_id' => $editor->id,
        ]);

        $editor->delete();

        $this->assertNull($post->fresh()->editor_id);
    }

    public function test_category_cannot_be_deleted_when_posts_still_exist(): void
    {
        $category = Category::factory()->create();
        Post::factory()->create([
            'category_id' => $category->id,
        ]);

        $this->expectException(QueryException::class);

        $category->delete();
    }

    public function test_pivot_is_removed_and_redirect_relation_is_nulled_when_post_is_force_deleted(): void
    {
        $post = Post::factory()->create();
        $tag = Tag::factory()->create();
        $post->tags()->attach($tag->id);
        $redirect = PostRedirect::factory()->create([
            'post_id' => $post->id,
        ]);

        $post->forceDelete();

        $this->assertDatabaseMissing('post_tag', [
            'post_id' => $post->id,
            'tag_id' => $tag->id,
        ]);
        $this->assertDatabaseHas('post_redirects', [
            'id' => $redirect->id,
            'post_id' => null,
        ]);
    }

    public function test_pivot_rows_are_removed_when_tag_is_deleted(): void
    {
        $post = Post::factory()->create();
        $tag = Tag::factory()->create();
        $post->tags()->attach($tag->id);

        $tag->delete();

        $this->assertDatabaseMissing('post_tag', [
            'post_id' => $post->id,
            'tag_id' => $tag->id,
        ]);
    }
}
