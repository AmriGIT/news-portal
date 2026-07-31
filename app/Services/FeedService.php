<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;

class FeedService
{
    /**
     * @return Collection<int, Post>
     */
    public function latest(int $limit = 20): Collection
    {
        return Post::query()
            ->select([
                'id',
                'author_id',
                'category_id',
                'title',
                'slug',
                'excerpt',
                'content',
                'seo_description',
                'canonical_url',
                'robots_index',
                'published_at',
                'updated_at',
            ])
            ->with([
                'author:id,name',
                'category:id,name,slug',
            ])
            ->published()
            ->where('robots_index', true)
            ->where(function ($query): void {
                $query
                    ->whereNull('canonical_url')
                    ->orWhere('canonical_url', '')
                    ->orWhere('canonical_url', 'like', rtrim(config('app.url'), '/').'%');
            })
            ->latestPublished()
            ->limit($limit)
            ->get();
    }

    public function description(Post $post): string
    {
        $candidate = $post->seo_description ?: $post->excerpt ?: $post->content;

        return str((string) $candidate)
            ->stripTags()
            ->squish()
            ->limit(300)
            ->toString();
    }
}
