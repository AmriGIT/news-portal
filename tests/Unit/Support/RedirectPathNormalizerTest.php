<?php

namespace Tests\Unit\Support;

use App\Support\RedirectPathNormalizer;
use InvalidArgumentException;
use Tests\TestCase;

class RedirectPathNormalizerTest extends TestCase
{
    public function test_it_normalizes_internal_paths(): void
    {
        $normalizer = new RedirectPathNormalizer;

        $this->assertSame('/berita/url-lama', $normalizer->normalize('berita//url-lama/'));
    }

    public function test_it_rejects_external_urls_query_and_fragment(): void
    {
        $normalizer = new RedirectPathNormalizer;

        foreach (['https://example.com/path', '/berita/lama?x=1', '/berita/lama#fragment'] as $path) {
            try {
                $normalizer->normalize($path);
                $this->fail('Path invalid seharusnya ditolak: '.$path);
            } catch (InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
    }
}
