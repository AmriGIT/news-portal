<?php

namespace App\Services;

use App\Models\PostRedirect;
use App\Support\RedirectPathNormalizer;

class RedirectResolver
{
    public function __construct(
        private readonly RedirectPathNormalizer $normalizer,
    ) {}

    public function resolve(string $path): ?PostRedirect
    {
        return PostRedirect::query()
            ->where('source_path', $this->normalizer->normalize($path))
            ->where('is_active', true)
            ->first();
    }

    public function recordHit(PostRedirect $redirect): void
    {
        $redirect->forceFill([
            'hit_count' => $redirect->hit_count + 1,
            'last_hit_at' => now(),
        ])->save();
    }
}
