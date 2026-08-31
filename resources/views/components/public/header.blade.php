@props(['site' => []])

@php
    $siteName = $site['name'] ?? config('app.name');
    $headerLogoUrl = asset('images/header-logo.webp');
@endphp

<header class="public-header">
    <div class="public-header__bar">
        <a href="{{ route('home') }}" class="public-header__logo-link" aria-label="{{ $siteName }}">
            <span class="public-header__logo-frame">
                <img
                    src="{{ $headerLogoUrl }}"
                    alt="{{ $siteName }}"
                    class="public-header__logo"
                    width="208"
                    height="48"
                    loading="eager"
                    decoding="async"
                >
            </span>
        </a>

        <x-public.navigation :categories="$navigationCategories ?? collect()" />

        <form action="{{ route('search') }}" method="GET" class="public-header__search" role="search">
            <label for="desktop-search" class="sr-only">Cari berita</label>
            <div class="public-header__search-box">
                <input
                    id="desktop-search"
                    name="q"
                    type="search"
                    value="{{ request('q') }}"
                    minlength="2"
                    maxlength="100"
                    placeholder="Cari berita"
                    class="public-header__search-input"
                >
                <button type="submit" class="public-header__search-button">
                    Cari
                </button>
            </div>
        </form>

        <button
            type="button"
            class="public-header__menu-button"
            data-mobile-menu-button
            aria-expanded="false"
            aria-controls="mobile-navigation"
        >
            <span class="sr-only">Buka menu navigasi</span>
            <span aria-hidden="true" class="public-header__menu-icon">
                <span></span>
                <span></span>
                <span></span>
            </span>
        </button>
    </div>

    <x-public.mobile-navigation :categories="$navigationCategories ?? collect()" />
</header>
