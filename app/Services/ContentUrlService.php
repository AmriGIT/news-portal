<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Support\RedirectPathNormalizer;
use Illuminate\Support\Facades\Storage;

class ContentUrlService
{
    public function __construct(
        private readonly RedirectPathNormalizer $normalizer,
    ) {}

    public function postPath(Post $post): string
    {
        return $this->joinPath(config('content.post_prefix', '/berita'), $post->slug);
    }

    public function postUrl(Post $post): string
    {
        return $this->absoluteUrl($this->postPath($post));
    }

    public function categoryPath(Category $category): string
    {
        return $this->joinPath(config('content.category_prefix', '/kategori'), $category->slug);
    }

    public function categoryUrl(Category $category): string
    {
        return $this->absoluteUrl($this->categoryPath($category));
    }

    public function tagPath(Tag $tag): string
    {
        return $this->joinPath(config('content.tag_prefix', '/tag'), $tag->slug);
    }

    public function tagUrl(Tag $tag): string
    {
        return $this->absoluteUrl($this->tagPath($tag));
    }

    public function postIndexPath(): string
    {
        return $this->normalizer->normalize(config('content.post_prefix', '/berita'));
    }

    public function postIndexUrl(): string
    {
        return $this->absoluteUrl($this->postIndexPath());
    }

    public function searchPath(): string
    {
        return '/cari';
    }

    public function searchUrl(?string $query = null): string
    {
        $url = $this->absoluteUrl($this->searchPath());

        if (filled($query)) {
            $url .= '?q='.rawurlencode(trim((string) $query));
        }

        return $url;
    }

    public function homeUrl(): string
    {
        return $this->absoluteUrl('/');
    }

    public function storageUrl(?string $path, string $disk = 'public'): ?string
    {
        if (blank($path)) {
            return null;
        }

        $path = trim((string) $path);

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        $url = Storage::disk($disk)->url($path);

        if (preg_match('#^https?://#i', $url) === 1) {
            return $url;
        }

        return $this->absoluteUrl($url);
    }

    private function joinPath(string $prefix, string $slug): string
    {
        return $this->normalizer->normalize(trim($prefix, '/').'/'.trim($slug, '/'));
    }

    private function absoluteUrl(string $path): string
    {
        $path = $this->normalizer->normalize($path);

        return rtrim(config('app.url'), '/').($path === '/' ? '' : $path);
    }
}
