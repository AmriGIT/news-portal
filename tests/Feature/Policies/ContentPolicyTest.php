<?php

namespace Tests\Feature\Policies;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\PostRedirect;
use App\Models\SiteSetting;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_categories_and_editor_can_only_view(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();
        $category = Category::factory()->create();

        $this->assertTrue($admin->can('viewAny', Category::class));
        $this->assertTrue($admin->can('view', $category));
        $this->assertTrue($admin->can('create', Category::class));
        $this->assertTrue($admin->can('update', $category));
        $this->assertTrue($admin->can('delete', $category));
        $this->assertTrue($editor->can('viewAny', Category::class));
        $this->assertTrue($editor->can('view', $category));
        $this->assertFalse($editor->can('create', Category::class));
        $this->assertFalse($editor->can('update', $category));
        $this->assertFalse($editor->can('delete', $category));
    }

    public function test_admin_can_manage_tags_and_editor_can_only_view(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();
        $tag = Tag::factory()->create();

        $this->assertTrue($admin->can('viewAny', Tag::class));
        $this->assertTrue($admin->can('view', $tag));
        $this->assertTrue($admin->can('create', Tag::class));
        $this->assertTrue($admin->can('update', $tag));
        $this->assertTrue($admin->can('delete', $tag));
        $this->assertTrue($editor->can('viewAny', Tag::class));
        $this->assertTrue($editor->can('view', $tag));
        $this->assertFalse($editor->can('create', Tag::class));
        $this->assertFalse($editor->can('update', $tag));
        $this->assertFalse($editor->can('delete', $tag));
    }

    public function test_admin_can_manage_redirects_and_settings_but_editor_cannot(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();
        $redirect = PostRedirect::factory()->create();
        $setting = SiteSetting::factory()->create();

        foreach ([PostRedirect::class => $redirect, SiteSetting::class => $setting] as $class => $model) {
            $this->assertTrue($admin->can('viewAny', $class));
            $this->assertTrue($admin->can('view', $model));
            $this->assertTrue($admin->can('create', $class));
            $this->assertTrue($admin->can('update', $model));
            $this->assertTrue($admin->can('delete', $model));
            $this->assertFalse($editor->can('viewAny', $class));
            $this->assertFalse($editor->can('view', $model));
            $this->assertFalse($editor->can('create', $class));
            $this->assertFalse($editor->can('update', $model));
            $this->assertFalse($editor->can('delete', $model));
        }
    }

    public function test_admin_can_manage_editors_but_not_admin_accounts_or_self_sensitive_actions(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();
        $regularEditor = User::factory()->editor()->create();

        $this->assertTrue($admin->can('viewAny', User::class));
        $this->assertTrue($admin->can('view', $editor));
        $this->assertTrue($admin->can('create', User::class));
        $this->assertTrue($admin->can('update', $editor));
        $this->assertTrue($admin->can('activate', $editor));
        $this->assertTrue($admin->can('deactivate', $editor));
        $this->assertTrue($admin->can('changeRole', [$editor, UserRole::Editor]));
        $this->assertFalse($admin->can('view', $otherAdmin));
        $this->assertFalse($admin->can('update', $otherAdmin));
        $this->assertFalse($admin->can('deactivate', $admin));
        $this->assertFalse($admin->can('changeRole', [$admin, UserRole::Editor]));
        $this->assertFalse($admin->can('delete', $otherAdmin));
        $this->assertFalse($admin->can('delete', $admin));
        $this->assertFalse($regularEditor->can('viewAny', User::class));
        $this->assertFalse($regularEditor->can('update', $editor));
    }
}
