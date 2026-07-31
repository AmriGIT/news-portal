<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostRedirect;
use App\Support\RedirectPathNormalizer;
use InvalidArgumentException;

class RedirectService
{
    public function __construct(
        private readonly RedirectPathNormalizer $normalizer,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function normalizeData(array $data, ?int $ignoreId = null): array
    {
        $sourcePath = $this->normalizer->normalize((string) ($data['source_path'] ?? ''));
        $destinationPath = $this->normalizer->normalize((string) ($data['destination_path'] ?? ''));
        $statusCode = (int) ($data['status_code'] ?? 301);

        $this->assertStatusCodeAllowed($statusCode);
        $this->assertValid($sourcePath, $destinationPath, $ignoreId);

        return [
            ...$data,
            'source_path' => $sourcePath,
            'destination_path' => $destinationPath,
            'status_code' => $statusCode,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'post_id' => filled($data['post_id'] ?? null) ? (int) $data['post_id'] : null,
        ];
    }

    public function upsert(
        string $sourcePath,
        string $destinationPath,
        int $statusCode = 301,
        ?Post $post = null,
        bool $isActive = true,
    ): PostRedirect {
        $sourcePath = $this->normalizer->normalize($sourcePath);
        $destinationPath = $this->normalizer->normalize($destinationPath);
        $existing = PostRedirect::query()->where('source_path', $sourcePath)->first();

        $this->assertStatusCodeAllowed($statusCode);
        $this->assertValid($sourcePath, $destinationPath, $existing?->id);

        return PostRedirect::query()->updateOrCreate([
            'source_path' => $sourcePath,
        ], [
            'destination_path' => $destinationPath,
            'status_code' => $statusCode,
            'post_id' => $post?->id,
            'is_active' => $isActive,
        ]);
    }

    public function flattenChains(Post $post, string $oldDestinationPath, string $newDestinationPath): void
    {
        $oldDestinationPath = $this->normalizer->normalize($oldDestinationPath);
        $newDestinationPath = $this->normalizer->normalize($newDestinationPath);

        PostRedirect::query()
            ->where('post_id', $post->id)
            ->where('is_active', true)
            ->where('destination_path', $oldDestinationPath)
            ->where('source_path', '!=', $newDestinationPath)
            ->update(['destination_path' => $newDestinationPath]);
    }

    public function assertValid(string $sourcePath, string $destinationPath, ?int $ignoreId = null): void
    {
        if ($sourcePath === $destinationPath) {
            throw new InvalidArgumentException('Path sumber dan tujuan tidak boleh sama.');
        }

        $duplicate = PostRedirect::query()
            ->where('source_path', $sourcePath)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();

        if ($duplicate) {
            throw new InvalidArgumentException('Path sumber sudah digunakan oleh redirect lain.');
        }

        $currentPath = $destinationPath;

        for ($depth = 0; $depth < 10; $depth++) {
            if ($currentPath === $sourcePath) {
                throw new InvalidArgumentException('Redirect loop terdeteksi.');
            }

            $next = PostRedirect::query()
                ->where('source_path', $currentPath)
                ->where('is_active', true)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->first();

            if (! $next) {
                return;
            }

            $currentPath = $this->normalizer->normalize($next->destination_path);
        }

        throw new InvalidArgumentException('Redirect chain terlalu panjang.');
    }

    private function assertStatusCodeAllowed(int $statusCode): void
    {
        if (! in_array($statusCode, [301, 302], true)) {
            throw new InvalidArgumentException('Kode redirect hanya boleh 301 atau 302.');
        }
    }
}
