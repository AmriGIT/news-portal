<?php

namespace App\Support;

use InvalidArgumentException;

class RedirectPathNormalizer
{
    public function normalize(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            throw new InvalidArgumentException('Path wajib diisi.');
        }

        $parts = parse_url($path);

        if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
            throw new InvalidArgumentException('Redirect hanya menerima path internal.');
        }

        if (isset($parts['query']) || isset($parts['fragment'])) {
            throw new InvalidArgumentException('Path tidak boleh memiliki query atau fragment.');
        }

        $normalized = '/'.ltrim($parts['path'] ?? $path, '/');
        $normalized = preg_replace('#/+#', '/', $normalized) ?: '/';
        $normalized = rawurldecode($normalized);

        if ($normalized !== '/') {
            $normalized = rtrim($normalized, '/');
        }

        if ($normalized === '') {
            $normalized = '/';
        }

        return $normalized;
    }
}
