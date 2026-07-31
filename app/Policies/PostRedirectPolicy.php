<?php

namespace App\Policies;

use App\Models\PostRedirect;
use App\Models\User;

class PostRedirectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    public function view(User $user, PostRedirect $postRedirect): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, PostRedirect $postRedirect): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, PostRedirect $postRedirect): bool
    {
        return $this->viewAny($user);
    }
}
