<?php

namespace App\Policies;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use App\Support\PostStatusTransitions;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && ($user->isAdmin() || $user->isEditor());
    }

    public function view(User $user, Post $post): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return $user->isAdmin() || $post->author_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Post $post): bool
    {
        if (! $this->view($user, $post)) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return in_array($post->status, [PostStatus::Draft, PostStatus::Review], true);
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    public function restore(User $user, Post $post): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    public function forceDelete(User $user, Post $post): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    public function submitForReview(User $user, Post $post): bool
    {
        return $this->canMoveTo($user, $post, PostStatus::Review);
    }

    public function returnToDraft(User $user, Post $post): bool
    {
        return $this->canMoveTo($user, $post, PostStatus::Draft);
    }

    public function schedule(User $user, Post $post): bool
    {
        return $this->canMoveTo($user, $post, PostStatus::Scheduled);
    }

    public function publish(User $user, Post $post): bool
    {
        return $this->canMoveTo($user, $post, PostStatus::Published);
    }

    public function archive(User $user, Post $post): bool
    {
        return $this->canMoveTo($user, $post, PostStatus::Archived);
    }

    private function canMoveTo(User $user, Post $post, PostStatus $targetStatus): bool
    {
        if (! $this->view($user, $post)) {
            return false;
        }

        return PostStatusTransitions::canTransitionForRole($user->role, $post->status, $targetStatus);
    }
}
