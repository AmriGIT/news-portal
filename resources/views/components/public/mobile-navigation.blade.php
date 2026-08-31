@props(['categories'])

@php
    $currentCategorySlug = request()->route('slug');
@endphp

<nav id="mobile-navigation" class="public-mobile-nav hidden" data-mobile-menu aria-label="Navigasi mobile">
    <form action="{{ route('search') }}" method="GET" class="mb-3" role="search">
        <label for="mobile-search" class="sr-only">Cari berita</label>
        <div class="flex h-11 overflow-hidden rounded-sm border border-slate-300 bg-white focus-within:border-bebas-blue focus-within:ring-2 focus-within:ring-bebas-blue">
            <input
                id="mobile-search"
                name="q"
                type="search"
                value="{{ request('q') }}"
                minlength="2"
                maxlength="100"
                placeholder="Cari berita"
                class="min-w-0 flex-1 border-0 bg-transparent px-3 text-sm text-bebas-navy outline-none placeholder:text-bebas-gray"
            >
            <button type="submit" class="shrink-0 bg-bebas-blue px-4 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-bebas-blue focus:ring-offset-2">
                Cari
            </button>
        </div>
    </form>

    <div class="grid gap-1">
        <a href="{{ route('home') }}" @class([
            'rounded-sm px-3 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-bebas-blue',
            'bg-blue-50 text-bebas-blue' => request()->routeIs('home'),
            'text-bebas-navy hover:bg-slate-100 hover:text-bebas-blue' => ! request()->routeIs('home'),
        ])>Beranda</a>
        <a href="{{ route('posts.index') }}" @class([
            'rounded-sm px-3 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-bebas-blue',
            'bg-blue-50 text-bebas-blue' => request()->routeIs('posts.index'),
            'text-bebas-navy hover:bg-slate-100 hover:text-bebas-blue' => ! request()->routeIs('posts.index'),
        ])>Berita</a>
        @foreach ($categories as $category)
            <a href="{{ route('categories.show', $category->slug) }}" @class([
                'rounded-sm px-3 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-bebas-blue',
                'bg-blue-50 text-bebas-blue' => request()->routeIs('categories.show') && $currentCategorySlug === $category->slug,
                'text-bebas-navy hover:bg-slate-100 hover:text-bebas-blue' => ! (request()->routeIs('categories.show') && $currentCategorySlug === $category->slug),
            ])>{{ $category->name }}</a>
        @endforeach
    </div>
</nav>
