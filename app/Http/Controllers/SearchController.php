<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicSearchRequest;
use App\Services\ContentUrlService;
use App\Services\PostSearchService;
use App\Services\SeoService;
use App\Services\StructuredDataService;
use Illuminate\Contracts\View\View;

class SearchController extends Controller
{
    public function __invoke(
        PublicSearchRequest $request,
        PostSearchService $searchService,
        SeoService $seoService,
        StructuredDataService $structuredData,
        ContentUrlService $urls,
    ): View {
        $keyword = $request->keyword();
        $posts = null;

        if (filled($keyword)) {
            $posts = $searchService
                ->search($keyword)
                ->paginate(12)
                ->withQueryString();

            if ($posts->isEmpty() && $posts->currentPage() > 1) {
                abort(404);
            }
        }

        $seo = $seoService->forSearch($keyword, (int) $request->query('page', 1));
        $breadcrumbs = [
            ['label' => 'Beranda', 'url' => $urls->homeUrl()],
            ['label' => 'Pencarian', 'url' => $seo->canonicalUrl],
        ];

        return view('search.index', [
            'seo' => $seo,
            'structuredData' => $structuredData->forSearch($keyword, $seo, $breadcrumbs),
            'breadcrumbs' => $breadcrumbs,
            'keyword' => $keyword,
            'posts' => $posts,
        ]);
    }
}
