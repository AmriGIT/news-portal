<?php

namespace App\Support;

use App\Enums\PostStatus;
use App\Enums\UserRole;

class PostStatusTransitions
{
    /**
     * @return array<string, list<PostStatus>>
     */
    public static function allowedForRole(UserRole $role): array
    {
        return match ($role) {
            UserRole::Admin => [
                PostStatus::Draft->value => [
                    PostStatus::Review,
                    PostStatus::Scheduled,
                    PostStatus::Archived,
                ],
                PostStatus::Review->value => [
                    PostStatus::Draft,
                    PostStatus::Scheduled,
                    PostStatus::Published,
                    PostStatus::Archived,
                ],
                PostStatus::Scheduled->value => [
                    PostStatus::Published,
                    PostStatus::Draft,
                    PostStatus::Archived,
                ],
                PostStatus::Published->value => [
                    PostStatus::Archived,
                ],
                PostStatus::Archived->value => [
                    PostStatus::Draft,
                ],
            ],
            UserRole::Editor => [
                PostStatus::Draft->value => [
                    PostStatus::Review,
                ],
                PostStatus::Review->value => [
                    PostStatus::Draft,
                ],
            ],
        };
    }

    public static function canTransition(PostStatus $from, PostStatus $to): bool
    {
        if ($from === $to) {
            return false;
        }

        return collect(self::allowedForRole(UserRole::Admin)[$from->value] ?? [])->contains($to);
    }

    public static function canTransitionForRole(UserRole $role, PostStatus $from, PostStatus $to): bool
    {
        if ($from === $to) {
            return false;
        }

        return collect(self::allowedForRole($role)[$from->value] ?? [])->contains($to);
    }
}
