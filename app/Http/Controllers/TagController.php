<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Tag;
use App\Services\ContentUrlService;
use App\Services\SeoService;
use App\Services\StructuredDataService;
use Illuminate\Contracts\View\View;

class TagController extends Controller
{
    public function show(string $slug, SeoService $seoService, StructuredDataService $structuredData, ContentUrlService $urls): View
    {
        $tag = Tag::query()
            ->where('slug', $slug)
            ->firstOrFail();

        $posts = Post::query()
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
            ->whereHas('tags', fn ($query) => $query->whereKey($tag->id))
            ->latestPublished()
            ->paginate(12);

        if ($posts->isEmpty() && $posts->currentPage() > 1) {
            abort(404);
        }

        $seo = $seoService->forTag($tag, $posts->currentPage(), $posts->total() > 0);
        $breadcrumbs = [
            ['label' => 'Beranda', 'url' => $urls->homeUrl()],
            ['label' => 'Tag', 'url' => $seo->canonicalUrl],
            ['label' => $tag->name, 'url' => $seo->canonicalUrl],
        ];

        return view('tags.show', [
            'seo' => $seo,
            'structuredData' => $structuredData->forTag($tag, $seo, $breadcrumbs),
            'breadcrumbs' => $breadcrumbs,
            'tag' => $tag,
            'posts' => $posts,
        ]);
    }
}
