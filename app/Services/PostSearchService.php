<?php

namespace App\Services;

use App\Models\Post;
use App\Support\LikePatternEscaper;
use Illuminate\Database\Eloquent\Builder;

class PostSearchService
{
    public function __construct(
        private readonly LikePatternEscaper $escaper,
    ) {}

    /**
     * @return Builder<Post>
     */
    public function search(string $keyword): Builder
    {
        $keyword = $this->normalize($keyword);
        $escaped = $this->escaper->escape($keyword);
        $contains = '%'.$escaped.'%';
        $startsWith = $escaped.'%';

        return Post::query()
            ->select([
                'id',
                'author_id',
                'category_id',
                'title',
                'slug',
                'excerpt',
                'featured_image',
                'featured_image_alt',
                'published_at',
            ])
            ->with([
                'author:id,name',
                'category:id,name,slug',
            ])
            ->published()
            ->where(function (Builder $query) use ($contains): void {
                $query
                    ->whereRaw("title LIKE ? ESCAPE '\\'", [$contains])
                    ->orWhereRaw("excerpt LIKE ? ESCAPE '\\'", [$contains])
                    ->orWhereRaw("content LIKE ? ESCAPE '\\'", [$contains])
                    ->orWhereHas('category', fn (Builder $query): Builder => $query->whereRaw("name LIKE ? ESCAPE '\\'", [$contains]));
            })
            ->orderByRaw(
                "CASE WHEN title = ? THEN 1 WHEN title LIKE ? ESCAPE '\\' THEN 2 WHEN title LIKE ? ESCAPE '\\' THEN 3 WHEN excerpt LIKE ? ESCAPE '\\' THEN 4 ELSE 5 END",
                [$keyword, $startsWith, $contains, $contains],
            )
            ->latestPublished();
    }

    public function normalize(string $keyword): string
    {
        $keyword = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $keyword) ?: '';

        return trim(preg_replace('/\s+/u', ' ', $keyword) ?: '');
    }
}
