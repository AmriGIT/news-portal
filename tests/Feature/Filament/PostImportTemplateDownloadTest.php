<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class PostImportTemplateDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_post_import_template_zip(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive belum tersedia.');
        }

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.posts.import-template'))
            ->assertOk()
            ->assertDownload('template-import-berita-bebasinfo.zip');
    }

    public function test_editor_cannot_download_post_import_template_zip(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)
            ->get(route('admin.posts.import-template'))
            ->assertForbidden();
    }
}
