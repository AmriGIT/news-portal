@props(['site' => []])

@php
    $siteName = $site['name'] ?? config('app.name');
    $headerLogoUrl = asset('images/header-logo.webp');
@endphp

<header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-[1400px] items-center justify-between gap-4 px-4 py-3 md:px-6">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center rounded-sm focus:outline-none focus:ring-2 focus:ring-bebas-blue focus:ring-offset-2" aria-label="{{ $siteName }}">
            <span class="block h-11 w-36 overflow-hidden sm:w-44 lg:w-48">
                <img
                    src="{{ $headerLogoUrl }}"
                    alt="{{ $siteName }}"
                    class="h-full w-full object-cover object-center"
                    width="208"
                    height="48"
                    loading="eager"
                    decoding="async"
                >
            </span>
        </a>

        <x-public.navigation :categories="$navigationCategories ?? collect()" />

        <form action="{{ route('search') }}" method="GET" class="hidden min-w-64 max-w-xs flex-1 lg:block" role="search">
            <label for="desktop-search" class="sr-only">Cari berita</label>
            <div class="flex h-10 overflow-hidden rounded-sm border border-slate-300 bg-white focus-within:border-bebas-blue focus-within:ring-2 focus-within:ring-bebas-blue">
                <input
                    id="desktop-search"
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

        <button
            type="button"
            class="inline-flex h-11 w-11 items-center justify-center rounded-sm border border-slate-300 text-bebas-navy hover:border-bebas-blue hover:text-bebas-blue focus:outline-none focus:ring-2 focus:ring-bebas-blue focus:ring-offset-2 lg:hidden"
            data-mobile-menu-button
            aria-expanded="false"
            aria-controls="mobile-navigation"
        >
            <span class="sr-only">Buka menu navigasi</span>
            <span aria-hidden="true" class="grid gap-1">
                <span class="block h-0.5 w-5 bg-current"></span>
                <span class="block h-0.5 w-5 bg-current"></span>
                <span class="block h-0.5 w-5 bg-current"></span>
            </span>
        </button>
    </div>

    <x-public.mobile-navigation :categories="$navigationCategories ?? collect()" />
</header>
