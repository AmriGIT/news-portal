<?php

namespace Tests\Unit\Support;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Support\PostStatusTransitions;
use PHPUnit\Framework\TestCase;

class PostStatusTransitionsTest extends TestCase
{
    public function test_valid_transitions_are_accepted(): void
    {
        $this->assertTrue(PostStatusTransitions::canTransition(PostStatus::Draft, PostStatus::Review));
        $this->assertTrue(PostStatusTransitions::canTransition(PostStatus::Review, PostStatus::Draft));
        $this->assertTrue(PostStatusTransitions::canTransition(PostStatus::Review, PostStatus::Scheduled));
        $this->assertTrue(PostStatusTransitions::canTransition(PostStatus::Review, PostStatus::Published));
        $this->assertTrue(PostStatusTransitions::canTransition(PostStatus::Scheduled, PostStatus::Published));
        $this->assertTrue(PostStatusTransitions::canTransition(PostStatus::Scheduled, PostStatus::Draft));
        $this->assertTrue(PostStatusTransitions::canTransition(PostStatus::Published, PostStatus::Archived));
        $this->assertTrue(PostStatusTransitions::canTransition(PostStatus::Archived, PostStatus::Draft));
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        $this->assertFalse(PostStatusTransitions::canTransition(PostStatus::Draft, PostStatus::Published));
        $this->assertFalse(PostStatusTransitions::canTransition(PostStatus::Published, PostStatus::Draft));
        $this->assertFalse(PostStatusTransitions::canTransition(PostStatus::Archived, PostStatus::Published));
    }

    public function test_transition_to_same_status_is_rejected(): void
    {
        foreach (PostStatus::cases() as $status) {
            $this->assertFalse(PostStatusTransitions::canTransition($status, $status));
        }
    }

    public function test_editor_only_has_two_allowed_transitions(): void
    {
        $this->assertTrue(PostStatusTransitions::canTransitionForRole(UserRole::Editor, PostStatus::Draft, PostStatus::Review));
        $this->assertTrue(PostStatusTransitions::canTransitionForRole(UserRole::Editor, PostStatus::Review, PostStatus::Draft));
        $this->assertFalse(PostStatusTransitions::canTransitionForRole(UserRole::Editor, PostStatus::Review, PostStatus::Published));
        $this->assertFalse(PostStatusTransitions::canTransitionForRole(UserRole::Editor, PostStatus::Draft, PostStatus::Scheduled));
        $this->assertFalse(PostStatusTransitions::canTransitionForRole(UserRole::Editor, PostStatus::Published, PostStatus::Archived));
    }

    public function test_admin_must_follow_valid_transitions(): void
    {
        $this->assertTrue(PostStatusTransitions::canTransitionForRole(UserRole::Admin, PostStatus::Review, PostStatus::Published));
        $this->assertFalse(PostStatusTransitions::canTransitionForRole(UserRole::Admin, PostStatus::Draft, PostStatus::Published));
    }
}
