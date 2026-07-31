@props(['categories'])

@php
    $currentCategorySlug = request()->route('slug');
@endphp

<nav class="hidden items-center gap-1 lg:flex" aria-label="Navigasi utama">
    <a href="{{ route('home') }}" @class([
        'rounded-sm border-b-2 px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-bebas-blue focus:ring-offset-2',
        'border-bebas-blue text-bebas-blue' => request()->routeIs('home'),
        'border-transparent text-bebas-navy hover:text-bebas-blue' => ! request()->routeIs('home'),
    ])>Beranda</a>
    <a href="{{ route('posts.index') }}" @class([
        'rounded-sm border-b-2 px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-bebas-blue focus:ring-offset-2',
        'border-bebas-blue text-bebas-blue' => request()->routeIs('posts.index'),
        'border-transparent text-bebas-navy hover:text-bebas-blue' => ! request()->routeIs('posts.index'),
    ])>Berita</a>
    @foreach ($categories as $category)
        <a href="{{ route('categories.show', $category->slug) }}" @class([
            'rounded-sm border-b-2 px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-bebas-blue focus:ring-offset-2',
            'border-bebas-blue text-bebas-blue' => request()->routeIs('categories.show') && $currentCategorySlug === $category->slug,
            'border-transparent text-bebas-navy hover:text-bebas-blue' => ! (request()->routeIs('categories.show') && $currentCategorySlug === $category->slug),
        ])>{{ $category->name }}</a>
    @endforeach
</nav>
