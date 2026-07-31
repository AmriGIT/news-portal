<?php

namespace Tests\Feature\Policies;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_posts(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();
        $reviewPost = Post::factory()->review()->create(['author_id' => $editor->id]);
        $publishedPost = Post::factory()->published()->create(['author_id' => $editor->id]);
        $scheduledPost = Post::factory()->scheduled()->create(['author_id' => $editor->id]);

        $this->assertTrue($admin->can('viewAny', Post::class));
        $this->assertTrue($admin->can('view', $reviewPost));
        $this->assertTrue($admin->can('create', Post::class));
        $this->assertTrue($admin->can('update', $reviewPost));
        $this->assertTrue($admin->can('delete', $reviewPost));
        $this->assertTrue($admin->can('restore', $reviewPost));
        $this->assertTrue($admin->can('forceDelete', $reviewPost));
        $this->assertTrue($admin->can('publish', $reviewPost));
        $this->assertTrue($admin->can('schedule', $reviewPost));
        $this->assertTrue($admin->can('archive', $publishedPost));
        $this->assertTrue($admin->can('archive', $scheduledPost));
    }

    public function test_admin_still_follows_status_transitions(): void
    {
        $admin = User::factory()->admin()->create();
        $draft = Post::factory()->draft()->create();
        $archived = Post::factory()->archived()->create();

        $this->assertFalse($admin->can('publish', $draft));
        $this->assertTrue($admin->can('returnToDraft', $archived));
    }

    public function test_editor_can_only_view_and_edit_owned_draft_or_review_posts(): void
    {
        $editor = User::factory()->editor()->create();
        $otherEditor = User::factory()->editor()->create();
        $ownedDraft = Post::factory()->draft()->create(['author_id' => $editor->id]);
        $ownedReview = Post::factory()->review()->create(['author_id' => $editor->id]);
        $ownedPublished = Post::factory()->published()->create(['author_id' => $editor->id]);
        $ownedScheduled = Post::factory()->scheduled()->create(['author_id' => $editor->id]);
        $otherDraft = Post::factory()->draft()->create(['author_id' => $otherEditor->id]);

        $this->assertTrue($editor->can('viewAny', Post::class));
        $this->assertTrue($editor->can('view', $ownedDraft));
        $this->assertFalse($editor->can('view', $otherDraft));
        $this->assertTrue($editor->can('create', Post::class));
        $this->assertTrue($editor->can('update', $ownedDraft));
        $this->assertTrue($editor->can('update', $ownedReview));
        $this->assertFalse($editor->can('update', $ownedPublished));
        $this->assertFalse($editor->can('update', $ownedScheduled));
        $this->assertFalse($editor->can('update', $otherDraft));
    }

    public function test_editor_status_actions_are_limited_to_owned_draft_and_review_posts(): void
    {
        $editor = User::factory()->editor()->create();
        $otherEditor = User::factory()->editor()->create();
        $ownedDraft = Post::factory()->draft()->create(['author_id' => $editor->id]);
        $ownedReview = Post::factory()->review()->create(['author_id' => $editor->id]);
        $otherDraft = Post::factory()->draft()->create(['author_id' => $otherEditor->id]);

        $this->assertTrue($editor->can('submitForReview', $ownedDraft));
        $this->assertFalse($editor->can('submitForReview', $otherDraft));
        $this->assertTrue($editor->can('returnToDraft', $ownedReview));
        $this->assertFalse($editor->can('publish', $ownedReview));
        $this->assertFalse($editor->can('schedule', $ownedReview));
        $this->assertFalse($editor->can('archive', $ownedReview));
        $this->assertFalse($editor->can('delete', $ownedDraft));
        $this->assertFalse($editor->can('restore', $ownedDraft));
        $this->assertFalse($editor->can('forceDelete', $ownedDraft));
    }

    public function test_inactive_user_cannot_manage_posts(): void
    {
        $inactiveAdmin = User::factory()->admin()->inactive()->create();
        $post = Post::factory()->create([
            'status' => PostStatus::Review,
        ]);

        $this->assertFalse($inactiveAdmin->can('viewAny', Post::class));
        $this->assertFalse($inactiveAdmin->can('publish', $post));
    }
}
