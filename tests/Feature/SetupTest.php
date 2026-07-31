<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_can_be_accessed(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }

    public function test_filament_login_page_can_be_accessed(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
    }

    public function test_guest_cannot_open_admin_dashboard(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_open_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.test',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
    }

    public function test_inactive_user_cannot_open_admin_dashboard(): void
    {
        $user = User::factory()->inactive()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertForbidden();
    }
}
