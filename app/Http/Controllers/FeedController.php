<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\ContentUrlService;
use App\Services\FeedService;
use App\Services\PublicCacheService;
use App\Services\SiteSettingService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class FeedController extends Controller
{
    public function __invoke(
        FeedService $feed,
        SiteSettingService $settings,
        ContentUrlService $urls,
        PublicCacheService $cache,
    ): Response {
        $xml = Cache::remember(PublicCacheService::FEED, $cache->feedTtl(), fn (): string => $this->rssXml($feed, $settings, $urls));

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    private function rssXml(FeedService $feed, SiteSettingService $settings, ContentUrlService $urls): string
    {
        $posts = $feed->latest(20);
        $latestDate = $posts->first()?->published_at ?? now();
        $logo = $urls->storageUrl(filled($settings->get('site_logo')) ? (string) $settings->get('site_logo') : null);
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<rss version="2.0">'."\n";
        $xml .= "  <channel>\n";
        $xml .= '    <title>'.$this->xml($settings->siteName())."</title>\n";
        $xml .= '    <link>'.$this->xml($urls->homeUrl())."</link>\n";
        $xml .= '    <description>'.$this->xml($settings->defaultSeoDescription() ?: $settings->siteDescription() ?: $settings->siteName())."</description>\n";
        $xml .= "    <language>id-ID</language>\n";
        $xml .= '    <lastBuildDate>'.$this->xml($latestDate->toRfc2822String())."</lastBuildDate>\n";

        if ($logo) {
            $xml .= "    <image>\n";
            $xml .= '      <url>'.$this->xml($logo)."</url>\n";
            $xml .= '      <title>'.$this->xml($settings->siteName())."</title>\n";
            $xml .= '      <link>'.$this->xml($urls->homeUrl())."</link>\n";
            $xml .= "    </image>\n";
        }

        foreach ($posts as $post) {
            $xml .= $this->itemXml($post, $feed, $urls);
        }

        return $xml."  </channel>\n</rss>\n";
    }

    private function itemXml(Post $post, FeedService $feed, ContentUrlService $urls): string
    {
        $url = $urls->postUrl($post);
        $xml = "    <item>\n";
        $xml .= '      <title>'.$this->xml($post->title)."</title>\n";
        $xml .= '      <link>'.$this->xml($url)."</link>\n";
        $xml .= '      <guid isPermaLink="true">'.$this->xml($url)."</guid>\n";
        $xml .= '      <description>'.$this->xml($feed->description($post))."</description>\n";
        $xml .= '      <pubDate>'.$this->xml($post->published_at->toRfc2822String())."</pubDate>\n";

        if ($post->category) {
            $xml .= '      <category>'.$this->xml($post->category->name)."</category>\n";
        }

        if ($post->author) {
            $xml .= '      <author>'.$this->xml($post->author->name)."</author>\n";
        }

        return $xml."    </item>\n";
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
