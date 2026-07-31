<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\ContentUrlService;
use App\Services\RedirectResolver;
use App\Services\SeoService;
use App\Services\StructuredDataService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request, SeoService $seoService, StructuredDataService $structuredData, ContentUrlService $urls): View
    {
        $posts = $this->cardQuery()
            ->paginate(12)
            ->withQueryString();

        if ($posts->isEmpty() && $posts->currentPage() > 1) {
            abort(404);
        }

        $seo = $seoService->forPostIndex((int) $request->query('page', 1));
        $breadcrumbs = [
            ['label' => 'Beranda', 'url' => $urls->homeUrl()],
            ['label' => 'Berita', 'url' => $seo->canonicalUrl],
        ];

        return view('posts.index', [
            'seo' => $seo,
            'structuredData' => $structuredData->forPostIndex($seo, $breadcrumbs),
            'breadcrumbs' => $breadcrumbs,
            'posts' => $posts,
        ]);
    }

    public function show(
        string $slug,
        Request $request,
        RedirectResolver $redirectResolver,
        SeoService $seoService,
        StructuredDataService $structuredData,
        ContentUrlService $urls,
    ): View|RedirectResponse {
        $post = Post::query()
            ->with([
                'author:id,name',
                'category:id,name,slug',
                'tags:id,name,slug',
            ])
            ->published()
            ->where('slug', $slug)
            ->first();

        if (! $post) {
            $redirect = $redirectResolver->resolve('/'.$request->path());

            if ($redirect) {
                $redirectResolver->recordHit($redirect);

                return redirect()->to($redirect->destination_path, $redirect->status_code);
            }

            abort(404);
        }

        $relatedPosts = $this->cardQuery()
            ->where('category_id', $post->category_id)
            ->whereKeyNot($post->id)
            ->limit(4)
            ->get();

        $seo = $seoService->forPost($post);
        $breadcrumbs = [
            ['label' => 'Beranda', 'url' => $urls->homeUrl()],
            ['label' => $post->category?->name ?? 'Kategori', 'url' => $post->category ? $urls->categoryUrl($post->category) : $seo->canonicalUrl],
            ['label' => $post->title, 'url' => $seo->canonicalUrl],
        ];

        return view('posts.show', [
            'seo' => $seo,
            'structuredData' => $structuredData->forPost($post, $seo, $breadcrumbs),
            'breadcrumbs' => $breadcrumbs,
            'post' => $post,
            'relatedPosts' => $relatedPosts,
        ]);
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
                'published_at',
            ])
            ->with([
                'author:id,name',
                'category:id,name,slug',
            ])
            ->published()
            ->latestPublished();
    }
}
