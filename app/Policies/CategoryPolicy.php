<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && ($user->isAdmin() || $user->isEditor());
    }

    public function view(User $user, Category $category): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    public function update(User $user, Category $category): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->isActive() && $user->isAdmin();
    }
}
