<?php

namespace App\Policies;

use App\Models\ImportToken;
use App\Models\User;

class ImportTokenPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    public function view(User $user, ImportToken $importToken): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, ImportToken $importToken): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, ImportToken $importToken): bool
    {
        return false;
    }
}
