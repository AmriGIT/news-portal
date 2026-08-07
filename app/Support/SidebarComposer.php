<?php

namespace App\Support;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class SidebarComposer
{
    public function compose(View $view): void
    {
        $view->with([
            'sidebarPopularPosts' => $this->popularPosts(),
            'sidebarTags' => $this->topTags(),
        ]);
    }

    /**
     * Get 5 featured/latest published posts for the sidebar.
     *
     * @return Collection<int, Post>
     */
    private function popularPosts(): Collection
    {
        $cached = Cache::get('sidebar:popular_posts');

        if ($cached instanceof Collection) {
            return $cached;
        }

        $posts = Post::query()
            ->select([
                'id',
                'title',
                'slug',
                'featured_image',
                'featured_image_alt',
                'published_at',
            ])
            ->published()
            ->featured()
            ->latestPublished()
            ->limit(5)
            ->get();

        Cache::put('sidebar:popular_posts', $posts, now()->addMinutes(15));

        return $posts;
    }

    /**
     * Get top 20 tags with post counts for tag cloud.
     *
     * @return Collection<int, Tag>
     */
    private function topTags(): Collection
    {
        $cached = Cache::get('sidebar:top_tags');

        if ($cached instanceof Collection) {
            return $cached;
        }

        $tags = Tag::query()
            ->withCount(['posts' => function ($query): void {
                $query->published();
            }])
            ->having('posts_count', '>', 0)
            ->orderByDesc('posts_count')
            ->limit(20)
            ->get();

        Cache::put('sidebar:top_tags', $tags, now()->addMinutes(30));

        return $tags;
    }
}
