@props([
    'popularPosts' => null,
    'tags' => null,
])

{{-- Ad slot atas --}}
<x-public.sidebar-ad slot-id="sidebar-top" format="rectangle" />

{{-- Berita Populer --}}
@if ($popularPosts?->isNotEmpty())
    <x-public.sidebar-popular-posts :posts="$popularPosts" />
@endif

{{-- Tag Cloud --}}
@if ($tags?->isNotEmpty())
    <x-public.sidebar-tag-cloud :tags="$tags" />
@endif

{{-- Newsletter --}}
<x-public.sidebar-newsletter />

{{-- Ad slot bawah (sticky area) --}}
<x-public.sidebar-ad slot-id="sidebar-bottom" format="half-page" />
