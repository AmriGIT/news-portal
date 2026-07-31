<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Generator;

class SitemapService
{
    public function __construct(
        private readonly ContentUrlService $urls,
    ) {}

    /**
     * @return array<int, array{loc: string, lastmod: string}>
     */
    public function indexes(): array
    {
        return [
            ['loc' => rtrim(config('app.url'), '/').'/sitemaps/posts.xml', 'lastmod' => now()->toIso8601String()],
            ['loc' => rtrim(config('app.url'), '/').'/sitemaps/categories.xml', 'lastmod' => now()->toIso8601String()],
            ['loc' => rtrim(config('app.url'), '/').'/sitemaps/tags.xml', 'lastmod' => now()->toIso8601String()],
        ];
    }

    /**
     * @return Generator<int, array{loc: string, lastmod: string}>
     */
    public function posts(): Generator
    {
        $posts = Post::query()
            ->select(['id', 'slug', 'updated_at', 'published_at', 'canonical_url', 'robots_index'])
            ->published()
            ->where('robots_index', true)
            ->where(function ($query): void {
                $query
                    ->whereNull('canonical_url')
                    ->orWhere('canonical_url', '')
                    ->orWhere('canonical_url', 'like', rtrim(config('app.url'), '/').'%');
            })
            ->orderBy('id')
            ->lazyById();

        foreach ($posts as $post) {
            if (filled($post->canonical_url) && ! str_starts_with((string) $post->canonical_url, rtrim(config('app.url'), '/'))) {
                continue;
            }

            yield [
                'loc' => $this->urls->postUrl($post),
                'lastmod' => $post->updated_at->toIso8601String(),
            ];
        }
    }

    /**
     * @return Generator<int, array{loc: string, lastmod: string}>
     */
    public function categories(): Generator
    {
        $categories = Category::query()
            ->select(['id', 'name', 'slug', 'updated_at'])
            ->where('is_active', true)
            ->whereHas('posts', fn ($query) => $query->published())
            ->orderBy('id')
            ->lazyById();

        foreach ($categories as $category) {
            yield [
                'loc' => $this->urls->categoryUrl($category),
                'lastmod' => $category->updated_at->toIso8601String(),
            ];
        }
    }

    /**
     * @return Generator<int, array{loc: string, lastmod: string}>
     */
    public function tags(): Generator
    {
        $tags = Tag::query()
            ->select(['id', 'name', 'slug', 'updated_at'])
            ->whereHas('posts', fn ($query) => $query->published())
            ->orderBy('id')
            ->lazyById();

        foreach ($tags as $tag) {
            yield [
                'loc' => $this->urls->tagUrl($tag),
                'lastmod' => $tag->updated_at->toIso8601String(),
            ];
        }
    }
}
