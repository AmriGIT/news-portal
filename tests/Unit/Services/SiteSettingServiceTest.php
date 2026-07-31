<?php

namespace Tests\Unit\Services;

use App\Models\SiteSetting;
use App\Services\SiteSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Tests\TestCase;

class SiteSettingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_defaults_casts_boolean_and_flushes_cache_after_update(): void
    {
        Cache::flush();

        $service = app(SiteSettingService::class);

        $this->assertSame(config('app.name'), $service->siteName());

        $service->set('default_robots_index', false);
        $this->assertFalse($service->get('default_robots_index'));

        SiteSetting::query()->where('key', 'default_robots_index')->update(['value' => '1']);
        $this->assertFalse($service->get('default_robots_index'));

        $service->forgetCache();
        $this->assertTrue($service->get('default_robots_index'));
    }

    public function test_it_rejects_sensitive_setting_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(SiteSettingService::class)->set('smtp_password', 'secret');
    }
}
