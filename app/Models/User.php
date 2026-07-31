<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use ValueError;

#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        if (! config('admin.login_enabled', true)) {
            return false;
        }

        if ($panel->getId() !== 'admin') {
            return false;
        }

        if (! $this->is_active) {
            return false;
        }

        try {
            return $this->isAdmin() || $this->isEditor();
        } catch (ValueError) {
            return false;
        }
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isEditor(): bool
    {
        return $this->role === UserRole::Editor;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    protected function role(): Attribute
    {
        return Attribute::make(
            get: fn (string $value): UserRole => UserRole::from($value),
            set: fn (UserRole|string $value): string => $value instanceof UserRole ? $value->value : $value,
        );
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function authoredPosts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function editedPosts(): HasMany
    {
        return $this->hasMany(Post::class, 'editor_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
