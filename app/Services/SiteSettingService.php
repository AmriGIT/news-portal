<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SiteSettingService
{
    private const CACHE_KEY = 'site_settings.all';

    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->normalizeKey($key);
        $settings = $this->all();

        return array_key_exists($key, $settings)
            ? $settings[$key]
            : data_get($this->definition($key), 'default', $default);
    }

    public function set(string $key, mixed $value): void
    {
        $this->setMany([$key => $value]);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $key = $this->normalizeKey($key);
            $this->assertSafeKey($key);
            $definition = $this->definition($key);
            $type = (string) data_get($definition, 'type', 'string');

            SiteSetting::query()->updateOrCreate([
                'key' => $key,
            ], [
                'value' => $this->serializeValue($value, $type),
                'type' => $type,
                'group' => (string) data_get($definition, 'group', 'general'),
                'is_public' => (bool) data_get($definition, 'public', false),
            ]);
        }

        $this->forgetCache();
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function siteName(): string
    {
        return (string) $this->get('site_name', config('app.name'));
    }

    public function siteDescription(): ?string
    {
        return $this->nullableString($this->get('site_description'));
    }

    public function defaultSeoTitle(): string
    {
        return (string) ($this->get('default_seo_title') ?: $this->siteName());
    }

    public function defaultSeoDescription(): ?string
    {
        return $this->nullableString($this->get('default_seo_description') ?: $this->siteDescription());
    }

    public function defaultOgImage(): ?string
    {
        return $this->nullableString($this->get('default_og_image'));
    }

    /**
     * @return array<string, mixed>
     */
    public function formData(): array
    {
        return collect($this->definitions())
            ->mapWithKeys(fn (array $definition, string $key): array => [$key => $this->get($key)])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            return SiteSetting::query()
                ->get()
                ->mapWithKeys(fn (SiteSetting $setting): array => [
                    $setting->key => $this->castValue($setting->value, $setting->type),
                ])
                ->all();
        });
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function definitions(): array
    {
        return config('site-settings.definitions', []);
    }

    /**
     * @return array<string, mixed>
     */
    private function definition(string $key): array
    {
        return $this->definitions()[$key] ?? [];
    }

    private function normalizeKey(string $key): string
    {
        return Str::slug($key, '_');
    }

    private function assertSafeKey(string $key): void
    {
        foreach (['password', 'secret', 'token', 'app_key', 'private_key', 'credential'] as $fragment) {
            if (str_contains($key, $fragment)) {
                throw new InvalidArgumentException('Setting sensitif tidak boleh disimpan di Site Settings.');
            }
        }
    }

    private function serializeValue(mixed $value, string $type): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($type === 'boolean') {
            return (bool) $value ? '1' : '0';
        }

        return (string) $value;
    }

    private function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        };
    }

    private function nullableString(mixed $value): ?string
    {
        return filled($value) ? (string) $value : null;
    }
}
