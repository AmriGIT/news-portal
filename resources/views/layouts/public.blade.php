@php
    $seo = $seo ?? null;
    $site = $publicSite ?? [];
    $siteName = $site['name'] ?? config('app.name');
    $robots = (($seo?->robotsIndex ?? true) ? 'index' : 'noindex') . ', ' . (($seo?->robotsFollow ?? true) ? 'follow' : 'nofollow');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seo->title ?? $siteName }}</title>
    @if (filled($seo?->description ?? null))
        <meta name="description" content="{{ $seo->description }}">
    @endif
    <link rel="canonical" href="{{ $seo->canonicalUrl ?? url()->current() }}">
    <meta name="robots" content="{{ $robots }}">
    <meta property="og:title" content="{{ $seo->ogTitle ?? ($seo->title ?? $siteName) }}">
    @if (filled($seo?->ogDescription ?? null))
        <meta property="og:description" content="{{ $seo->ogDescription }}">
    @endif
    <meta property="og:url" content="{{ $seo->canonicalUrl ?? url()->current() }}">
    <meta property="og:type" content="{{ $seo->ogType ?? 'website' }}">
    @if (filled($seo?->ogImage ?? null))
        <meta property="og:image" content="{{ $seo->ogImage }}">
        @if (filled($seo?->ogImageAlt ?? null))
            <meta property="og:image:alt" content="{{ $seo->ogImageAlt }}">
        @endif
    @endif
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="id_ID">
    @if (($seo?->ogType ?? null) === 'article')
        @if (filled($seo?->articlePublishedTime ?? null))
            <meta property="article:published_time" content="{{ $seo->articlePublishedTime }}">
        @endif
        @if (filled($seo?->articleModifiedTime ?? null))
            <meta property="article:modified_time" content="{{ $seo->articleModifiedTime }}">
        @endif
        @if (filled($seo?->articleSection ?? null))
            <meta property="article:section" content="{{ $seo->articleSection }}">
        @endif
        @foreach (($seo?->articleTags ?? []) as $tag)
            <meta property="article:tag" content="{{ $tag }}">
        @endforeach
    @endif
    <meta name="twitter:card" content="{{ $seo->twitterCard ?? 'summary' }}">
    <meta name="twitter:title" content="{{ $seo->ogTitle ?? ($seo->title ?? $siteName) }}">
    @if (filled($seo?->ogDescription ?? null))
        <meta name="twitter:description" content="{{ $seo->ogDescription }}">
    @endif
    @if (filled($seo?->ogImage ?? null))
        <meta name="twitter:image" content="{{ $seo->ogImage }}">
        @if (filled($seo?->ogImageAlt ?? null))
            <meta name="twitter:image:alt" content="{{ $seo->ogImageAlt }}">
        @endif
    @endif
    <link rel="alternate" type="application/rss+xml" title="RSS {{ $siteName }}" href="{{ route('feed') }}">
    @if (isset($posts) && method_exists($posts, 'previousPageUrl') && $posts->previousPageUrl())
        <link rel="prev" href="{{ $posts->previousPageUrl() }}">
    @endif
    @if (isset($posts) && method_exists($posts, 'nextPageUrl') && $posts->nextPageUrl())
        <link rel="next" href="{{ $posts->nextPageUrl() }}">
    @endif
    @if (filled($site['faviconUrl'] ?? null))
        <link rel="icon" href="{{ $site['faviconUrl'] }}">
    @endif
    @if (! empty($structuredData ?? []))
        <script type="application/ld+json">
            @json($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        </script>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-bebas-light text-bebas-navy antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-bebas-navy focus:shadow focus:outline-none focus:ring-2 focus:ring-bebas-blue">
        Lewati ke konten utama
    </a>

    <x-public.header :site="$site" />

    <main id="main-content" tabindex="-1" class="min-h-screen outline-none">
        {{ $slot }}
    </main>

    <x-public.footer :site="$site" />
</body>
</html>
