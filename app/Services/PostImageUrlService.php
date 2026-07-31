<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class PostImageUrlService
{
    public function original(?string $path): string
    {
        return $this->url($path);
    }

    public function large(?string $path): string
    {
        return $this->url($this->variantPath($path, 'large'));
    }

    public function medium(?string $path): string
    {
        return $this->url($this->variantPath($path, 'medium'));
    }

    public function thumbnail(?string $path): string
    {
        return $this->url($this->variantPath($path, 'thumbnail'));
    }

    public function alt(?string $altText, ?string $postTitle = null): string
    {
        foreach ([$altText, $postTitle, config('media.featured.default_alt'), config('app.name')] as $candidate) {
            $candidate = trim(strip_tags((string) $candidate));

            if ($candidate !== '') {
                return $candidate;
            }
        }

        return 'Gambar berita';
    }

    /**
     * @return array<string, string>
     */
    public function srcset(?string $path): array
    {
        $original = $this->original($path);

        return array_filter([
            '480w' => $this->thumbnail($path) ?: $original,
            '960w' => $this->medium($path) ?: $original,
            '1600w' => $this->large($path) ?: $original,
        ]);
    }

    public function srcsetAttribute(?string $path): ?string
    {
        $srcset = $this->srcset($path);

        if ($srcset === []) {
            return null;
        }

        return collect($srcset)
            ->map(fn (string $url, string $descriptor): string => "{$url} {$descriptor}")
            ->implode(', ');
    }

    private function variantPath(?string $path, string $variant): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (preg_match('#^https?://#i', (string) $path) === 1) {
            return (string) $path;
        }

        $info = pathinfo((string) $path);
        $directory = ($info['dirname'] ?? '.') === '.' ? '' : $info['dirname'].'/';
        $filename = $info['filename'] ?? '';
        $extension = $info['extension'] ?? 'webp';

        if ($filename === '') {
            return (string) $path;
        }

        return "{$directory}{$filename}-{$variant}.{$extension}";
    }

    private function url(?string $path): string
    {
        if (blank($path)) {
            return $this->defaultUrl();
        }

        $path = (string) $path;

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        return $this->absoluteUrl(Storage::disk(config('media.disk', 'public'))->url($path));
    }

    private function defaultUrl(): string
    {
        $path = trim((string) config('media.featured.default_image', '/images/default.png'));

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        return $this->absoluteUrl($path);
    }

    private function absoluteUrl(string $url): string
    {
        if (preg_match('#^https?://#i', $url) === 1) {
            return $url;
        }

        return rtrim(config('app.url'), '/').'/'.ltrim($url, '/');
    }
}
