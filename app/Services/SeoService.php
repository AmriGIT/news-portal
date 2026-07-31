<?php

namespace App\Services;

use App\Data\SeoData;
use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SeoService
{
    public function __construct(
        private readonly SiteSettingService $settings,
        private readonly ContentUrlService $urls,
        private readonly PostImageUrlService $images,
    ) {}

    public function forPost(Post $post): SeoData
    {
        $pageTitle = $this->withSiteName($post->seo_title ?: $post->title ?: $this->settings->defaultSeoTitle());
        $description = $this->description([
            $post->seo_description,
            $post->excerpt,
            $post->content,
            $this->settings->defaultSeoDescription(),
            $this->settings->siteDescription(),
        ]);

        [$robotsIndex, $robotsFollow] = $this->postRobots($post);
        $ogImage = $this->absoluteImage($post->og_image ?: $post->featured_image ?: $this->settings->defaultOgImage() ?: $this->settings->get('site_logo'))
            ?: $this->images->original(null);

        return new SeoData(
            title: $pageTitle,
            description: $description,
            canonicalUrl: $this->canonicalUrl($post->canonical_url, $this->urls->postUrl($post)),
            robotsIndex: $robotsIndex,
            robotsFollow: $robotsFollow,
            ogTitle: $this->withSiteName($post->seo_title ?: $post->title ?: $this->settings->defaultSeoTitle()),
            ogDescription: $description,
            ogImage: $ogImage,
            ogType: 'article',
            twitterCard: $ogImage ? 'summary_large_image' : 'summary',
            ogImageAlt: $this->images->alt($post->featured_image_alt, $post->title),
            articlePublishedTime: $post->published_at?->timezone(config('app.timezone'))->toIso8601String(),
            articleModifiedTime: $post->updated_at?->timezone(config('app.timezone'))->toIso8601String(),
            articleSection: $post->category?->name,
            articleTags: $post->relationLoaded('tags') ? $post->tags->pluck('name')->values()->all() : [],
        );
    }

    public function forCategory(Category $category, ?int $page = null): SeoData
    {
        $pageTitle = $this->withSiteName($category->seo_title ?: $category->name ?: $this->settings->defaultSeoTitle());
        $description = $this->description([
            $category->seo_description,
            $category->description,
            $this->settings->defaultSeoDescription(),
            $this->settings->siteDescription(),
        ]);
        $ogImage = $this->siteImage();
        $robotsIndex = $category->is_active && (bool) $this->settings->get('default_robots_index', true);
        $robotsFollow = $category->is_active && (bool) $this->settings->get('default_robots_follow', true);

        $canonicalUrl = $this->urls->categoryUrl($category);

        if ($page && $page > 1) {
            $canonicalUrl .= '?page='.$page;
        }

        return new SeoData(
            title: $pageTitle,
            description: $description,
            canonicalUrl: $canonicalUrl,
            robotsIndex: $robotsIndex,
            robotsFollow: $robotsFollow,
            ogTitle: $pageTitle,
            ogDescription: $description,
            ogImage: $ogImage,
            ogType: 'website',
            twitterCard: $ogImage ? 'summary_large_image' : 'summary',
        );
    }

    public function forPostIndex(?int $page = null): SeoData
    {
        $title = $this->withSiteName('Berita Terbaru');
        $description = $this->description([
            $this->settings->defaultSeoDescription(),
            $this->settings->siteDescription(),
        ]);
        $canonicalUrl = $this->urls->postIndexUrl();

        if ($page && $page > 1) {
            $canonicalUrl .= '?page='.$page;
        }

        $ogImage = $this->siteImage();

        return new SeoData(
            title: $title,
            description: $description,
            canonicalUrl: $canonicalUrl,
            robotsIndex: (bool) $this->settings->get('default_robots_index', true),
            robotsFollow: (bool) $this->settings->get('default_robots_follow', true),
            ogTitle: $title,
            ogDescription: $description,
            ogImage: $ogImage,
            ogType: 'website',
            twitterCard: $ogImage ? 'summary_large_image' : 'summary',
        );
    }

    public function forSearch(string $query, ?int $page = null): SeoData
    {
        $query = $this->normalizeSearchQuery($query);
        $title = $this->withSiteName(filled($query) ? 'Hasil Pencarian "'.$query.'"' : 'Pencarian Berita');
        $description = filled($query)
            ? 'Hasil pencarian berita untuk "'.$query.'" di '.$this->settings->siteName().'.'
            : 'Cari berita terbaru di '.$this->settings->siteName().'.';
        $canonicalUrl = $this->urls->searchUrl($query);

        if ($page && $page > 1 && filled($query)) {
            $canonicalUrl .= '&page='.$page;
        }

        return new SeoData(
            title: $title,
            description: $this->cleanDescription($description),
            canonicalUrl: $canonicalUrl,
            robotsIndex: false,
            robotsFollow: true,
            ogTitle: $title,
            ogDescription: $this->cleanDescription($description),
            ogImage: $this->siteImage(),
            ogType: 'website',
            twitterCard: 'summary_large_image',
        );
    }

    public function forTag(Tag $tag, ?int $page = null, bool $hasPublishedPosts = true): SeoData
    {
        $title = $this->withSiteName('Berita Tag '.$tag->name);
        $description = $this->description([
            $tag->description,
            'Kumpulan berita dengan tag '.$tag->name.'.',
            $this->settings->defaultSeoDescription(),
        ]);
        $ogImage = $this->siteImage();

        $canonicalUrl = $this->urls->tagUrl($tag);

        if ($page && $page > 1) {
            $canonicalUrl .= '?page='.$page;
        }

        return new SeoData(
            title: $title,
            description: $description,
            canonicalUrl: $canonicalUrl,
            robotsIndex: $hasPublishedPosts && (bool) $this->settings->get('default_robots_index', true),
            robotsFollow: (bool) $this->settings->get('default_robots_follow', true),
            ogTitle: $title,
            ogDescription: $description,
            ogImage: $ogImage,
            ogType: 'website',
            twitterCard: $ogImage ? 'summary_large_image' : 'summary',
        );
    }

    public function forHome(): SeoData
    {
        $pageTitle = $this->withSiteName($this->settings->defaultSeoTitle());
        $description = $this->description([
            $this->settings->defaultSeoDescription(),
            $this->settings->siteDescription(),
        ]);
        $ogImage = $this->siteImage();

        return new SeoData(
            title: $pageTitle,
            description: $description,
            canonicalUrl: $this->urls->homeUrl(),
            robotsIndex: (bool) $this->settings->get('default_robots_index', true),
            robotsFollow: (bool) $this->settings->get('default_robots_follow', true),
            ogTitle: $pageTitle,
            ogDescription: $description,
            ogImage: $ogImage,
            ogType: 'website',
            twitterCard: $ogImage ? 'summary_large_image' : 'summary',
        );
    }

    private function withSiteName(string $title): string
    {
        $title = trim($title);
        $siteName = trim($this->settings->siteName());

        if ($siteName === '' || Str::contains(Str::lower($title), Str::lower($siteName))) {
            return Str::limit($title, 120, '');
        }

        return Str::limit($title.' | '.$siteName, 120, '');
    }

    /**
     * @param  array<int, mixed>  $candidates
     */
    private function description(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            $text = $this->cleanDescription($candidate);

            if (filled($text)) {
                return Str::limit($text, 170);
            }
        }

        return null;
    }

    private function cleanDescription(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $value))) ?: '');
    }

    private function canonicalUrl(?string $customUrl, string $fallback): string
    {
        if (blank($customUrl)) {
            return $fallback;
        }

        $customUrl = trim($customUrl);
        $scheme = parse_url($customUrl, PHP_URL_SCHEME);

        if (! in_array($scheme, ['http', 'https'], true) || filter_var($customUrl, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('Canonical URL harus absolut dengan scheme HTTP atau HTTPS.');
        }

        return $customUrl;
    }

    /**
     * @return array{bool, bool}
     */
    private function postRobots(Post $post): array
    {
        if ($post->status === PostStatus::Published) {
            return [(bool) $post->robots_index, (bool) $post->robots_follow];
        }

        if ($post->status === PostStatus::Archived) {
            return [false, true];
        }

        return [false, false];
    }

    private function absoluteImage(mixed $path): ?string
    {
        return $this->urls->storageUrl(filled($path) ? (string) $path : null);
    }

    private function siteImage(): string
    {
        return $this->absoluteImage($this->settings->defaultOgImage() ?: $this->settings->get('site_logo'))
            ?: $this->images->original(null);
    }

    private function normalizeSearchQuery(string $query): string
    {
        return trim(preg_replace('/\s+/u', ' ', $query) ?: '');
    }
}
