<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\ManageSiteSettings;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\SiteSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ManageSiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_settings_and_editor_cannot(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();

        $this->actingAs($admin)->get(ManageSiteSettings::getUrl())->assertOk();
        $this->actingAs($editor)->get(ManageSiteSettings::getUrl())->assertForbidden();
    }

    public function test_admin_can_save_settings_and_cache_is_refreshed(): void
    {
        Cache::flush();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(ManageSiteSettings::class)
            ->fillForm([
                'site_name' => 'Portal Baru',
                'contact_email' => 'redaksi@example.test',
                'default_robots_index' => false,
                'default_robots_follow' => true,
                'facebook_url' => 'https://facebook.com/portal',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $service = app(SiteSettingService::class);

        $this->assertSame('Portal Baru', $service->siteName());
        $this->assertFalse($service->get('default_robots_index'));
        $this->assertDatabaseHas('site_settings', [
            'key' => 'contact_email',
            'value' => 'redaksi@example.test',
            'type' => 'email',
        ]);
    }

    public function test_settings_validation_rejects_invalid_email_and_url(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(ManageSiteSettings::class)
            ->fillForm([
                'site_name' => 'Portal',
                'contact_email' => 'bukan-email',
                'facebook_url' => 'bukan-url',
            ])
            ->call('save')
            ->assertHasFormErrors([
                'contact_email' => 'email',
                'facebook_url' => 'url',
            ]);
    }

    public function test_logo_upload_and_replace_deletes_old_file_after_success(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        Storage::disk('public')->put('site/branding/old-logo.png', 'old');
        SiteSetting::factory()->create([
            'key' => 'site_logo',
            'value' => 'site/branding/old-logo.png',
            'type' => 'image',
            'group' => 'general',
        ]);

        $this->actingAs($admin);

        Livewire::test(ManageSiteSettings::class)
            ->fillForm([
                'site_logo' => null,
            ])
            ->fillForm([
                'site_name' => 'Portal',
                'site_logo' => [UploadedFile::fake()->image('logo.png', 400, 200)->size(300)],
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        Storage::disk('public')->assertMissing('site/branding/old-logo.png');

        $path = app(SiteSettingService::class)->get('site_logo');

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }
}
