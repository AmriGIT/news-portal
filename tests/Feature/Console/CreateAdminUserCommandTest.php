<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_active_admin_without_printing_password(): void
    {
        $password = 'StrongPass123!';

        $this->artisan('admin:create', [
            '--name' => 'Admin Production',
            '--email' => 'admin@example.com',
            '--password' => $password,
            '--force' => true,
        ])
            ->expectsOutput('User Admin berhasil dibuat.')
            ->doesntExpectOutput($password)
            ->assertSuccessful();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertSame('Admin Production', $admin->name);
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertTrue(Hash::check($password, $admin->password));
        $this->assertNotSame($password, $admin->password);
    }

    public function test_command_rejects_duplicate_email_invalid_email_and_weak_password(): void
    {
        User::factory()->editor()->create(['email' => 'existing@example.com']);

        $this->artisan('admin:create', [
            '--name' => 'Admin Production',
            '--email' => 'existing@example.com',
            '--password' => 'StrongPass123!',
            '--force' => true,
        ])->assertFailed();

        $this->artisan('admin:create', [
            '--name' => 'Admin Production',
            '--email' => 'not-an-email',
            '--password' => 'StrongPass123!',
            '--force' => true,
        ])->assertFailed();

        $this->artisan('admin:create', [
            '--name' => 'Admin Production',
            '--email' => 'new@example.com',
            '--password' => 'password',
            '--force' => true,
        ])->assertFailed();

        $this->assertDatabaseMissing('users', [
            'email' => 'new@example.com',
        ]);
        $this->assertSame(UserRole::Editor, User::query()->where('email', 'existing@example.com')->firstOrFail()->role);
    }

    public function test_command_can_be_cancelled(): void
    {
        $this->artisan('admin:create', [
            '--name' => 'Admin Production',
            '--email' => 'cancel@example.com',
            '--password' => 'StrongPass123!',
        ])
            ->expectsConfirmation('Buat user Admin ini?', 'no')
            ->expectsOutput('Pembuatan admin dibatalkan.')
            ->assertFailed();

        $this->assertDatabaseMissing('users', [
            'email' => 'cancel@example.com',
        ]);
    }
}
