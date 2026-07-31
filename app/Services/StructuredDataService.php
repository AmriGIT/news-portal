<?php

namespace App\Services;

use App\Data\SeoData;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;

class StructuredDataService
{
    public function __construct(
        private readonly SiteSettingService $settings,
        private readonly ContentUrlService $urls,
        private readonly PostImageUrlService $images,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forHome(SeoData $seo): array
    {
        return [
            $this->website($seo),
            $this->organization(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forPost(Post $post, SeoData $seo, array $breadcrumbs): array
    {
        return [
            $this->newsArticle($post, $seo),
            $this->breadcrumbList($breadcrumbs),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forPostIndex(SeoData $seo, array $breadcrumbs): array
    {
        return [
            $this->collectionPage($seo),
            $this->breadcrumbList($breadcrumbs),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forCategory(Category $category, SeoData $seo, array $breadcrumbs): array
    {
        return [
            $this->collectionPage($seo, 'Berita kategori '.$category->name),
            $this->breadcrumbList($breadcrumbs),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forTag(Tag $tag, SeoData $seo, array $breadcrumbs): array
    {
        return [
            $this->collectionPage($seo, 'Berita tag '.$tag->name),
            $this->breadcrumbList($breadcrumbs),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forSearch(string $query, SeoData $seo, array $breadcrumbs): array
    {
        return [
            $this->collectionPage($seo, filled($query) ? 'Hasil pencarian '.$query : 'Pencarian berita'),
            $this->breadcrumbList($breadcrumbs),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function website(SeoData $seo): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $this->settings->siteName(),
            'url' => $this->urls->homeUrl(),
            'description' => $seo->description,
            'publisher' => [
                '@type' => 'NewsMediaOrganization',
                'name' => $this->settings->siteName(),
            ],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => $this->urls->searchUrl().'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ], $this->present(...));
    }

    /**
     * @return array<string, mixed>
     */
    private function organization(): array
    {
        $logo = $this->urls->storageUrl(filled($this->settings->get('site_logo')) ? (string) $this->settings->get('site_logo') : null);
        $sameAs = array_values(array_filter([
            $this->settings->get('facebook_url'),
            $this->settings->get('instagram_url'),
            $this->settings->get('youtube_url'),
            $this->settings->get('x_url'),
            $this->settings->get('tiktok_url'),
        ], fn (mixed $url): bool => is_string($url) && filter_var($url, FILTER_VALIDATE_URL) !== false));

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'NewsMediaOrganization',
            'name' => $this->settings->siteName(),
            'url' => $this->urls->homeUrl(),
            'logo' => $logo ? [
                '@type' => 'ImageObject',
                'url' => $logo,
            ] : null,
            'sameAs' => $sameAs,
            'contactPoint' => filled($this->settings->get('contact_email')) ? [
                '@type' => 'ContactPoint',
                'email' => $this->settings->get('contact_email'),
                'contactType' => 'redaksi',
            ] : null,
        ], $this->present(...));
    }

    /**
     * @return array<string, mixed>
     */
    private function newsArticle(Post $post, SeoData $seo): array
    {
        $imageUrls = array_values(array_filter([
            $this->images->large($post->featured_image),
            $this->images->medium($post->featured_image),
            $this->images->original($post->featured_image),
        ]));

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $post->title,
            'description' => $seo->description,
            'datePublished' => $post->published_at?->timezone(config('app.timezone'))->toIso8601String(),
            'dateModified' => $post->updated_at?->timezone(config('app.timezone'))->toIso8601String(),
            'mainEntityOfPage' => $seo->canonicalUrl,
            'url' => $this->urls->postUrl($post),
            'author' => [
                '@type' => 'Person',
                'name' => $post->author?->name ?? 'Redaksi',
            ],
            'publisher' => $this->organization(),
            'image' => $imageUrls,
            'articleSection' => $post->category?->name,
            'keywords' => $post->relationLoaded('tags') ? $post->tags->pluck('name')->values()->all() : [],
            'isAccessibleForFree' => true,
            'wordCount' => str_word_count(strip_tags($post->content)),
        ], fn (mixed $value): bool => is_bool($value) || $this->present($value));
    }

    /**
     * @param  array<int, array{label: string, url: string}>  $breadcrumbs
     * @return array<string, mixed>
     */
    private function breadcrumbList(array $breadcrumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($breadcrumbs)
                ->values()
                ->map(fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['label'],
                    'item' => $item['url'],
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectionPage(SeoData $seo, ?string $name = null): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $name ?: $seo->title,
            'description' => $seo->description,
            'url' => $seo->canonicalUrl,
        ], fn (mixed $value): bool => filled($value));
    }

    private function present(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }

        return filled($value);
    }
}
