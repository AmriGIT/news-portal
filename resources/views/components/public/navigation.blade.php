@props(['categories'])

@php
    $currentCategorySlug = request()->route('slug');
@endphp

<nav class="public-nav" aria-label="Navigasi utama">
    <a href="{{ route('home') }}" @class([
        'public-nav__link',
        'public-nav__link--active' => request()->routeIs('home'),
        'public-nav__link--inactive' => ! request()->routeIs('home'),
    ])>Beranda</a>
    <a href="{{ route('posts.index') }}" @class([
        'public-nav__link',
        'public-nav__link--active' => request()->routeIs('posts.index'),
        'public-nav__link--inactive' => ! request()->routeIs('posts.index'),
    ])>Berita</a>
    @foreach ($categories as $category)
        <a href="{{ route('categories.show', $category->slug) }}" @class([
            'public-nav__link',
            'public-nav__link--active' => request()->routeIs('categories.show') && $currentCategorySlug === $category->slug,
            'public-nav__link--inactive' => ! (request()->routeIs('categories.show') && $currentCategorySlug === $category->slug),
        ])>{{ $category->name }}</a>
    @endforeach
</nav>
