<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostRedirect;

class PostSlugRedirectService
{
    public function __construct(
        private readonly ContentUrlService $urlService,
        private readonly RedirectService $redirectService,
    ) {}

    public function handle(Post $post, string $oldSlug, string $newSlug): ?PostRedirect
    {
        if ($oldSlug === $newSlug) {
            return null;
        }

        $oldPath = $this->urlService->postPath(new Post(['slug' => $oldSlug]));
        $newPath = $this->urlService->postPath(new Post(['slug' => $newSlug]));

        $redirect = $this->redirectService->upsert(
            sourcePath: $oldPath,
            destinationPath: $newPath,
            statusCode: 301,
            post: $post,
            isActive: true,
        );

        $this->redirectService->flattenChains($post, $oldPath, $newPath);

        return $redirect;
    }
}
