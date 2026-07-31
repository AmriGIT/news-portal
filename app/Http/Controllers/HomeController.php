<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Services\PublicCacheService;
use App\Services\SeoService;
use App\Services\StructuredDataService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function __invoke(SeoService $seoService, StructuredDataService $structuredData, PublicCacheService $cache): View
    {
        $payload = Cache::remember(PublicCacheService::HOMEPAGE, $cache->homepageTtl(), fn (): array => $this->homepagePayload());
        $data = $this->hydrateHomepageData($payload);
        $seo = $seoService->forHome();

        return view('home', [
            'seo' => $seo,
            'structuredData' => $structuredData->forHome($seo),
            ...$data,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function homepagePayload(): array
    {
        $heroPostId = $this->cardQuery()
            ->where('is_featured', true)
            ->value('id');

        if (! $heroPostId) {
            $heroPostId = $this->cardQuery()->value('id');
        }

        $excludedIds = collect([$heroPostId])->filter()->all();

        $featuredPostIds = $this->cardQuery()
            ->where('is_featured', true)
            ->when($excludedIds !== [], fn (Builder $query) => $query->whereNotIn('id', $excludedIds))
            ->limit(4)
            ->pluck('id')
            ->all();

        $excludedIds = collect($excludedIds)
            ->merge($featuredPostIds)
            ->all();

        if (count($featuredPostIds) < 4) {
            $fallbackPostIds = $this->cardQuery()
                ->when($excludedIds !== [], fn (Builder $query) => $query->whereNotIn('id', $excludedIds))
                ->limit(4 - count($featuredPostIds))
                ->pluck('id')
                ->all();

            $featuredPostIds = collect($featuredPostIds)->merge($fallbackPostIds)->all();
            $excludedIds = collect($excludedIds)->merge($fallbackPostIds)->all();
        }

        $latestPostIds = $this->cardQuery()
            ->when($excludedIds !== [], fn (Builder $query) => $query->whereNotIn('id', $excludedIds))
            ->limit(10)
            ->pluck('id')
            ->all();

        $categorySections = $this->categorySectionPayload();

        return [
            'heroPostId' => $heroPostId,
            'featuredPostIds' => $featuredPostIds,
            'latestPostIds' => $latestPostIds,
            'categorySections' => $categorySections,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function hydrateHomepageData(array $payload): array
    {
        return [
            'heroPost' => filled($payload['heroPostId'] ?? null)
                ? $this->cardQuery()->whereKey($payload['heroPostId'])->first()
                : null,
            'featuredPosts' => $this->hydratePostsByIds($payload['featuredPostIds'] ?? []),
            'latestPosts' => $this->hydratePostsByIds($payload['latestPostIds'] ?? []),
            'categorySections' => $this->hydrateCategorySections($payload['categorySections'] ?? []),
        ];
    }

    /**
     * @return Builder<Post>
     */
    private function cardQuery(): Builder
    {
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
                'is_featured',
                'published_at',
            ])
            ->with([
                'author:id,name',
                'category:id,name,slug',
            ])
            ->published()
            ->latestPublished();
    }

    /**
     * @return Collection<int, array{category: Category, posts: Collection<int, Post>}>
     */
    private function categorySectionPayload(): array
    {
        return Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(3)
            ->get()
            ->map(fn (Category $category): array => [
                'categoryId' => $category->id,
                'postIds' => $this->cardQuery()
                    ->where('category_id', $category->id)
                    ->limit(4)
                    ->pluck('id')
                    ->all(),
            ])
            ->filter(fn (array $section): bool => $section['postIds'] !== [])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, Post>
     */
    private function hydratePostsByIds(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $posts = $this->cardQuery()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(fn (int $id) => $posts->get($id))
            ->filter()
            ->values();
    }

    /**
     * @param  array<int, array{categoryId: int, postIds: array<int, int>}>  $sections
     * @return Collection<int, array{category: Category, posts: Collection<int, Post>}>
     */
    private function hydrateCategorySections(array $sections): Collection
    {
        if ($sections === []) {
            return collect();
        }

        $categoryIds = collect($sections)->pluck('categoryId')->all();
        $categories = Category::query()
            ->select(['id', 'name', 'slug', 'description'])
            ->whereIn('id', $categoryIds)
            ->get()
            ->keyBy('id');

        return collect($sections)
            ->map(fn (array $section): array => [
                'category' => $categories->get($section['categoryId']),
                'posts' => $this->hydratePostsByIds($section['postIds']),
            ])
            ->filter(fn (array $section): bool => $section['category'] instanceof Category && $section['posts']->isNotEmpty())
            ->values();
    }
}
