<?php

namespace App\Actions\Post;

use App\Enums\PostStatus;
use App\Exceptions\InvalidPostStatusTransitionException;
use App\Models\Post;
use App\Models\User;
use App\Support\PostStatusTransitions;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TransitionPostStatusAction
{
    public function execute(User $actor, Post $post, PostStatus $targetStatus, ?CarbonInterface $publishedAt = null): Post
    {
        $post->refresh();

        Gate::forUser($actor)->authorize('view', $post);

        if (! PostStatusTransitions::canTransitionForRole($actor->role, $post->status, $targetStatus)) {
            throw InvalidPostStatusTransitionException::invalidTransition();
        }

        Gate::forUser($actor)->authorize($this->abilityFor($targetStatus), $post);

        $this->validatePublicationRequirements($post, $targetStatus, $publishedAt);

        return DB::transaction(function () use ($actor, $post, $targetStatus, $publishedAt): Post {
            $post->status = $targetStatus;

            match ($targetStatus) {
                PostStatus::Draft,
                PostStatus::Review => $post->published_at = null,
                PostStatus::Scheduled => $post->published_at = $publishedAt,
                PostStatus::Published => $post->published_at = now(),
                PostStatus::Archived => null,
            };

            if (in_array($targetStatus, [PostStatus::Scheduled, PostStatus::Published], true)) {
                $post->editor_id = $actor->id;
            }

            $post->save();

            return $post->refresh();
        });
    }

    private function abilityFor(PostStatus $targetStatus): string
    {
        return match ($targetStatus) {
            PostStatus::Review => 'submitForReview',
            PostStatus::Draft => 'returnToDraft',
            PostStatus::Scheduled => 'schedule',
            PostStatus::Published => 'publish',
            PostStatus::Archived => 'archive',
        };
    }

    private function validatePublicationRequirements(Post $post, PostStatus $targetStatus, ?CarbonInterface $publishedAt): void
    {
        if (in_array($targetStatus, [PostStatus::Review, PostStatus::Scheduled, PostStatus::Published], true)) {
            $validator = Validator::make($post->getAttributes(), [
                'author_id' => ['required', 'exists:users,id'],
                'category_id' => ['required', 'exists:categories,id'],
                'title' => ['required', 'string', 'max:255'],
                'slug' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('posts', 'slug')->ignore($post->id),
                ],
                'content' => ['required', 'string'],
            ]);

            if ($validator->fails()) {
                throw InvalidPostStatusTransitionException::missingPublicationRequirements();
            }
        }

        if ($targetStatus === PostStatus::Scheduled && (! $publishedAt || $publishedAt->lessThanOrEqualTo(now()))) {
            throw InvalidPostStatusTransitionException::scheduleMustBeInFuture();
        }
    }
}
