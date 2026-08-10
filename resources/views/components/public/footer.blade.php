@props(['site' => []])

@php
    $footerLogoUrl = asset('images/logo-footer.webp');
    $hasContact = filled($site['contactEmail'] ?? null)
        || filled($site['contactPhone'] ?? null)
        || filled($site['contactAddress'] ?? null)
        || ! empty($site['socialLinks'] ?? []);
@endphp

<footer class="mt-16 bg-bebas-navy text-white">
    <div @class([
        'mx-auto grid max-w-[1400px] gap-8 px-4 py-10 md:px-6',
        'md:grid-cols-3' => $hasContact,
        'md:grid-cols-2' => ! $hasContact,
    ])>
        <div>
            <a href="{{ route('home') }}" class="inline-flex rounded-sm focus:outline-none focus:ring-2 focus:ring-bebas-blue focus:ring-offset-2 focus:ring-offset-bebas-navy" aria-label="{{ $site['name'] ?? config('app.name') }}">
                <span class="block h-14 w-44 overflow-hidden">
                    <img
                        src="{{ $footerLogoUrl }}"
                        alt="{{ $site['name'] ?? config('app.name') }}"
                        class="h-full w-full object-cover object-center"
                        width="176"
                        height="56"
                        loading="lazy"
                        decoding="async"
                    >
                </span>
            </a>
            @if (filled($site['description'] ?? null))
                <p class="mt-3 text-sm leading-6 text-white/70">{{ $site['description'] }}</p>
            @endif
            @if (filled($site['footerText'] ?? null))
                <p class="mt-3 text-sm leading-6 text-white/70">{{ $site['footerText'] }}</p>
            @endif
        </div>

        <div>
            <p class="text-sm font-semibold uppercase tracking-normal text-white/50">Navigasi</p>
            <div class="mt-3 grid gap-2 text-sm">
                <a href="{{ route('home') }}" class="text-white/75 hover:text-white focus:outline-none focus:ring-2 focus:ring-bebas-blue">Beranda</a>
                <a href="{{ route('posts.index') }}" class="text-white/75 hover:text-white focus:outline-none focus:ring-2 focus:ring-bebas-blue">Berita Terbaru</a>
            </div>
        </div>

        @if ($hasContact)
            <div>
                <p class="text-sm font-semibold uppercase tracking-normal text-white/50">Kontak</p>
                <div class="mt-3 grid gap-2 text-sm text-white/75">
                    @if (filled($site['contactEmail'] ?? null))
                        <a href="mailto:{{ $site['contactEmail'] }}" class="hover:text-white focus:outline-none focus:ring-2 focus:ring-bebas-blue">{{ $site['contactEmail'] }}</a>
                    @endif
                    @if (filled($site['contactPhone'] ?? null))
                        <p>{{ $site['contactPhone'] }}</p>
                    @endif
                    @if (filled($site['contactAddress'] ?? null))
                        <p>{{ $site['contactAddress'] }}</p>
                    @endif
                </div>

                @if (! empty($site['socialLinks'] ?? []))
                    <div class="mt-4 flex flex-wrap gap-3 text-sm">
                        @foreach ($site['socialLinks'] as $label => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="text-white/75 hover:text-white focus:outline-none focus:ring-2 focus:ring-bebas-blue">{{ $label }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>
    <div class="border-t border-white/10 px-4 py-4 text-center text-xs text-white/50">
        &copy; {{ now()->year }} {{ $site['name'] ?? config('app.name') }}.
    </div>
</footer>
