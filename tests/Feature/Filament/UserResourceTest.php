<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_editor_list(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(UserResource::getUrl('index'))
            ->assertOk();
    }

    public function test_editor_cannot_open_user_resource_urls(): void
    {
        $editor = User::factory()->editor()->create();
        $target = User::factory()->editor()->create();

        $this->actingAs($editor);

        $this->get(UserResource::getUrl('index'))->assertForbidden();
        $this->get(UserResource::getUrl('create'))->assertForbidden();
        $this->get(UserResource::getUrl('edit', ['record' => $target]))->assertForbidden();
    }

    public function test_editor_list_only_shows_editor_accounts(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();
        $otherEditor = User::factory()->editor()->create();

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords(collect([$editor, $otherEditor]))
            ->assertCanNotSeeTableRecords(collect([$admin]));
    }

    public function test_admin_can_create_editor_with_hashed_password_and_editor_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Editor Baru',
                'email' => 'EDITOR.BARU@example.test',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();

        $editor = User::query()->where('email', 'editor.baru@example.test')->firstOrFail();

        $this->assertSame(UserRole::Editor, $editor->role);
        $this->assertTrue($editor->is_active);
        $this->assertTrue(Hash::check('secret-password', $editor->password));
        $this->assertNotSame('secret-password', $editor->password);
    }

    public function test_email_must_be_unique_when_creating_editor(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->editor()->create(['email' => 'used@example.test']);

        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Editor Duplikat',
                'email' => 'used@example.test',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'unique']);
    }

    public function test_admin_can_edit_editor_without_changing_password_when_password_is_empty(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();
        $oldPassword = $editor->password;

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $editor->id])
            ->fillForm([
                'name' => 'Editor Updated',
                'email' => 'updated@example.test',
                'password' => null,
                'password_confirmation' => null,
                'is_active' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $editor->refresh();

        $this->assertSame('Editor Updated', $editor->name);
        $this->assertSame('updated@example.test', $editor->email);
        $this->assertSame($oldPassword, $editor->password);
        $this->assertSame(UserRole::Editor, $editor->role);
    }

    public function test_admin_can_change_editor_password(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();
        $oldPassword = $editor->password;

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $editor->id])
            ->fillForm([
                'name' => $editor->name,
                'email' => $editor->email,
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
                'is_active' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $editor->refresh();

        $this->assertNotSame($oldPassword, $editor->password);
        $this->assertTrue(Hash::check('new-secret-password', $editor->password));
    }

    public function test_admin_can_deactivate_and_activate_editor(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('deactivate')->table($editor))
            ->assertNotified();

        $this->assertFalse($editor->fresh()->is_active);

        $this->actingAs($editor->fresh())
            ->get('/admin')
            ->assertForbidden();

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('activate')->table($editor->fresh()))
            ->assertNotified();

        $this->assertTrue($editor->fresh()->is_active);
    }

    public function test_admin_account_cannot_be_manipulated_through_editor_resource(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(UserResource::getUrl('edit', ['record' => $otherAdmin]))
            ->assertNotFound();
    }
}
