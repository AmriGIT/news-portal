<?php

namespace Tests\Unit\Actions;

use App\Actions\Post\TransitionPostStatusAction;
use App\Enums\PostStatus;
use App\Exceptions\InvalidPostStatusTransitionException;
use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransitionPostStatusActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_action_runs_authorization(): void
    {
        $editor = User::factory()->editor()->create();
        $otherEditor = User::factory()->editor()->create();
        $post = Post::factory()->draft()->create(['author_id' => $otherEditor->id]);

        $this->expectException(AuthorizationException::class);

        app(TransitionPostStatusAction::class)->execute($editor, $post, PostStatus::Review);
    }

    public function test_action_rejects_invalid_transition_and_keeps_record_unchanged(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->draft()->create();

        $this->expectException(InvalidPostStatusTransitionException::class);

        try {
            app(TransitionPostStatusAction::class)->execute($admin, $post, PostStatus::Published);
        } finally {
            $post->refresh();

            $this->assertSame(PostStatus::Draft, $post->status);
            $this->assertNull($post->published_at);
        }
    }

    public function test_action_saves_status_and_returns_fresh_model(): void
    {
        $editor = User::factory()->editor()->create();
        $post = Post::factory()->draft()->create(['author_id' => $editor->id]);

        $result = app(TransitionPostStatusAction::class)->execute($editor, $post, PostStatus::Review);

        $this->assertSame(PostStatus::Review, $result->status);
        $this->assertSame(PostStatus::Review, $post->fresh()->status);
        $this->assertNull($result->published_at);
    }

    public function test_schedule_requires_future_time_and_sets_published_at(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->review()->create();
        $publishedAt = now()->addDay()->setSecond(0);

        $result = app(TransitionPostStatusAction::class)->execute($admin, $post, PostStatus::Scheduled, $publishedAt);

        $this->assertSame(PostStatus::Scheduled, $result->status);
        $this->assertTrue($result->published_at->isSameSecond($publishedAt));
        $this->assertSame($admin->id, $result->editor_id);
    }

    public function test_schedule_rejects_past_time(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->review()->create();

        $this->expectException(InvalidPostStatusTransitionException::class);

        app(TransitionPostStatusAction::class)->execute($admin, $post, PostStatus::Scheduled, now()->subMinute());
    }

    public function test_publish_sets_current_time_and_editor_id(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->review()->create();

        $this->travelTo(now()->setSecond(0));

        $result = app(TransitionPostStatusAction::class)->execute($admin, $post, PostStatus::Published);

        $this->assertSame(PostStatus::Published, $result->status);
        $this->assertTrue($result->published_at->isSameSecond(now()));
        $this->assertSame($admin->id, $result->editor_id);
    }

    public function test_archive_keeps_existing_published_at_and_archived_to_draft_clears_it(): void
    {
        $admin = User::factory()->admin()->create();
        $publishedAt = now()->subDay()->setSecond(0);
        $post = Post::factory()->published()->create(['published_at' => $publishedAt]);

        $archived = app(TransitionPostStatusAction::class)->execute($admin, $post, PostStatus::Archived);

        $this->assertSame(PostStatus::Archived, $archived->status);
        $this->assertTrue($archived->published_at->isSameSecond($publishedAt));

        $draft = app(TransitionPostStatusAction::class)->execute($admin, $archived, PostStatus::Draft);

        $this->assertSame(PostStatus::Draft, $draft->status);
        $this->assertNull($draft->published_at);
    }
}
