<?php

namespace App\Policies;

use App\Models\NewsImport;
use App\Models\User;

class NewsImportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    public function view(User $user, NewsImport $newsImport): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, NewsImport $newsImport): bool
    {
        return false;
    }

    public function delete(User $user, NewsImport $newsImport): bool
    {
        return false;
    }
}
