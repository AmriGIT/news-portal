<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Services\ContentUrlService;
use App\Services\SeoService;
use App\Services\StructuredDataService;
use Illuminate\Contracts\View\View;

class CategoryController extends Controller
{
    public function show(string $slug, SeoService $seoService, StructuredDataService $structuredData, ContentUrlService $urls): View
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->where('is_active', true)
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
            ->where('category_id', $category->id)
            ->latestPublished()
            ->paginate(12);

        if ($posts->isEmpty() && $posts->currentPage() > 1) {
            abort(404);
        }

        $seo = $seoService->forCategory($category, $posts->currentPage());
        $breadcrumbs = [
            ['label' => 'Beranda', 'url' => $urls->homeUrl()],
            ['label' => 'Kategori', 'url' => $seo->canonicalUrl],
            ['label' => $category->name, 'url' => $seo->canonicalUrl],
        ];

        return view('categories.show', [
            'seo' => $seo,
            'structuredData' => $structuredData->forCategory($category, $seo, $breadcrumbs),
            'breadcrumbs' => $breadcrumbs,
            'category' => $category,
            'posts' => $posts,
        ]);
    }
}
