<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    public function view(User $user, User $target): bool
    {
        return $this->viewAny($user) && $target->isEditor();
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, User $target): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return $target->isEditor() && $target->id !== $user->id;
    }

    public function delete(User $user, User $target): bool
    {
        return false;
    }

    public function restore(User $user, User $target): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $target): bool
    {
        return false;
    }

    public function deactivate(User $user, User $target): bool
    {
        return $this->update($user, $target);
    }

    public function activate(User $user, User $target): bool
    {
        return $this->update($user, $target);
    }

    public function changeRole(User $user, User $target, UserRole $newRole): bool
    {
        if (! $this->update($user, $target)) {
            return false;
        }

        return $target->isEditor() && $newRole === UserRole::Editor;
    }
}
