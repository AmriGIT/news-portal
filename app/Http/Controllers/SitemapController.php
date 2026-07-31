<?php

namespace App\Http\Controllers;

use App\Services\PublicCacheService;
use App\Services\SitemapService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index(SitemapService $sitemap, PublicCacheService $cache): Response
    {
        $xml = Cache::remember(PublicCacheService::SITEMAP_INDEX, $cache->sitemapTtl(), fn (): string => $this->sitemapIndexXml($sitemap->indexes()));

        return $this->xmlResponse($xml, 900);
    }

    public function posts(SitemapService $sitemap, PublicCacheService $cache): Response
    {
        $xml = Cache::remember(PublicCacheService::SITEMAP_POSTS, $cache->sitemapTtl(), fn (): string => $this->urlsetXml($sitemap->posts()));

        return $this->xmlResponse($xml, 900);
    }

    public function categories(SitemapService $sitemap, PublicCacheService $cache): Response
    {
        $xml = Cache::remember(PublicCacheService::SITEMAP_CATEGORIES, $cache->sitemapTtl(), fn (): string => $this->urlsetXml($sitemap->categories()));

        return $this->xmlResponse($xml, 900);
    }

    public function tags(SitemapService $sitemap, PublicCacheService $cache): Response
    {
        $xml = Cache::remember(PublicCacheService::SITEMAP_TAGS, $cache->sitemapTtl(), fn (): string => $this->urlsetXml($sitemap->tags()));

        return $this->xmlResponse($xml, 900);
    }

    /**
     * @param  array<int, array{loc: string, lastmod: string}>  $items
     */
    private function sitemapIndexXml(array $items): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($items as $item) {
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>'.$this->xml($item['loc'])."</loc>\n";
            $xml .= '    <lastmod>'.$this->xml($item['lastmod'])."</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }

        return $xml."</sitemapindex>\n";
    }

    /**
     * @param  iterable<array{loc: string, lastmod: string}>  $items
     */
    private function urlsetXml(iterable $items): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($items as $item) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.$this->xml($item['loc'])."</loc>\n";
            $xml .= '    <lastmod>'.$this->xml($item['lastmod'])."</lastmod>\n";
            $xml .= "  </url>\n";
        }

        return $xml."</urlset>\n";
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function xmlResponse(string $xml, int $maxAge): Response
    {
        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age='.$maxAge,
        ]);
    }
}
