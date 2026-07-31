<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_admin_can_access_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_active_editor_can_access_panel(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)->get('/admin')->assertOk();
    }

    public function test_inactive_admin_cannot_access_panel(): void
    {
        $admin = User::factory()->admin()->inactive()->create();

        $this->actingAs($admin)->get('/admin')->assertForbidden();
    }

    public function test_inactive_editor_cannot_access_panel(): void
    {
        $editor = User::factory()->editor()->inactive()->create();

        $this->actingAs($editor)->get('/admin')->assertForbidden();
    }

    public function test_unknown_role_cannot_access_panel(): void
    {
        $user = User::factory()->create();

        DB::table('users')
            ->where('id', $user->id)
            ->update(['role' => 'viewer']);

        $this->actingAs($user->fresh())->get('/admin')->assertForbidden();
    }

    public function test_disabled_admin_login_redirects_login_page_to_home(): void
    {
        config(['admin.login_enabled' => false]);

        $this->get('/admin/login')->assertRedirect('/');
    }

    public function test_disabled_admin_login_redirects_admin_panel_to_home_even_for_authenticated_admin(): void
    {
        config(['admin.login_enabled' => false]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertRedirect('/');
        $this->assertFalse($admin->fresh()->canAccessPanel(filament()->getPanel('admin')));
    }

    public function test_user_role_helpers_use_enum_values(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();

        $freshAdmin = $admin->fresh();
        $freshEditor = $editor->fresh();

        $this->assertSame(UserRole::Admin, $freshAdmin->role);
        $this->assertTrue($freshAdmin->isAdmin());
        $this->assertFalse($freshAdmin->isEditor());
        $this->assertSame(UserRole::Editor, $freshEditor->role);
        $this->assertTrue($freshEditor->isEditor());
        $this->assertFalse($freshEditor->isAdmin());
    }
}
