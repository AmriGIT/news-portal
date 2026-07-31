@props([
    'path' => null,
    'alt' => '',
    'class' => '',
    'sizes' => '(min-width: 1024px) 50vw, 100vw',
    'loading' => 'lazy',
    'fetchpriority' => null,
    'width' => 1600,
    'height' => 900,
])

@php
    $imageUrls = app(\App\Services\PostImageUrlService::class);
    $src = $imageUrls->original($path);
    $srcset = $imageUrls->srcsetAttribute($path);
    $altText = $imageUrls->alt($alt);
@endphp

@if ($src)
    <img
        src="{{ $src }}"
        @if ($srcset) srcset="{{ $srcset }}" @endif
        sizes="{{ $sizes }}"
        alt="{{ $altText }}"
        width="{{ $width }}"
        height="{{ $height }}"
        loading="{{ $loading }}"
        @if ($loading !== 'eager') decoding="async" @endif
        @if ($fetchpriority) fetchpriority="{{ $fetchpriority }}" @endif
        {{ $attributes->merge(['class' => $class]) }}
    >
@else
    <div {{ $attributes->merge(['class' => 'flex aspect-video items-center justify-center bg-slate-200 text-sm font-semibold text-bebas-gray '.$class]) }} role="img" aria-label="{{ $altText }}">
        <span>{{ $publicSite['name'] ?? config('app.name') }}</span>
    </div>
@endif
