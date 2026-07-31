<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\PostRedirects\Pages\CreatePostRedirect;
use App\Filament\Resources\PostRedirects\Pages\EditPostRedirect;
use App\Filament\Resources\PostRedirects\Pages\ListPostRedirects;
use App\Filament\Resources\PostRedirects\PostRedirectResource;
use App\Models\PostRedirect;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PostRedirectResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_resource_and_editor_cannot(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();

        $this->actingAs($admin)->get(PostRedirectResource::getUrl('index'))->assertOk();
        $this->actingAs($editor)->get(PostRedirectResource::getUrl('index'))->assertForbidden();
    }

    public function test_admin_can_create_edit_delete_redirect(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(CreatePostRedirect::class)
            ->fillForm([
                'source_path' => 'berita//lama/',
                'destination_path' => '/berita/baru',
                'status_code' => 302,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $redirect = PostRedirect::query()->where('source_path', '/berita/lama')->firstOrFail();

        $this->assertSame('/berita/baru', $redirect->destination_path);
        $this->assertSame(302, $redirect->status_code);

        Livewire::test(EditPostRedirect::class, ['record' => $redirect->id])
            ->fillForm([
                'source_path' => '/berita/lama',
                'destination_path' => '/berita/terbaru',
                'status_code' => 301,
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('post_redirects', [
            'id' => $redirect->id,
            'destination_path' => '/berita/terbaru',
            'status_code' => 301,
            'is_active' => false,
        ]);

        Livewire::test(EditPostRedirect::class, ['record' => $redirect->id])
            ->callAction(DeleteAction::class)
            ->assertNotified();

        $this->assertDatabaseMissing('post_redirects', ['id' => $redirect->id]);
    }

    public function test_redirect_form_rejects_loop_external_path_and_invalid_status(): void
    {
        $admin = User::factory()->admin()->create();
        PostRedirect::factory()->create([
            'source_path' => '/berita/b',
            'destination_path' => '/berita/a',
        ]);

        $this->actingAs($admin);

        Livewire::test(CreatePostRedirect::class)
            ->fillForm([
                'source_path' => '/berita/a',
                'destination_path' => '/berita/b',
                'status_code' => 301,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['source_path']);

        Livewire::test(CreatePostRedirect::class)
            ->fillForm([
                'source_path' => 'https://example.com/lama',
                'destination_path' => '/berita/baru',
                'status_code' => 301,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['source_path']);

        Livewire::test(CreatePostRedirect::class)
            ->fillForm([
                'source_path' => '/berita/x',
                'destination_path' => '/berita/y',
                'status_code' => 307,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['status_code']);
    }

    public function test_table_search_and_filters_work(): void
    {
        $admin = User::factory()->admin()->create();
        $target = PostRedirect::factory()->temporary()->create([
            'source_path' => '/berita/search-me',
            'destination_path' => '/berita/result-me',
            'is_active' => true,
        ]);
        $other = PostRedirect::factory()->inactive()->create([
            'source_path' => '/berita/lain',
            'destination_path' => '/berita/tujuan-lain',
            'status_code' => 301,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListPostRedirects::class)
            ->searchTable('search-me')
            ->assertCanSeeTableRecords(collect([$target]))
            ->assertCanNotSeeTableRecords(collect([$other]))
            ->searchTable('')
            ->filterTable('status_code', 302)
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords(collect([$target]))
            ->assertCanNotSeeTableRecords(collect([$other]));
    }
}
