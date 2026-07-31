<?php

namespace App\Policies;

use App\Models\SiteSetting;
use App\Models\User;

class SiteSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    public function view(User $user, SiteSetting $siteSetting): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, SiteSetting $siteSetting): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, SiteSetting $siteSetting): bool
    {
        return $this->viewAny($user);
    }
}
