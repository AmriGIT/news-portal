@props(['items' => []])

<nav aria-label="Breadcrumb" class="mb-8">
    <ol class="flex flex-wrap items-center gap-2 text-sm text-bebas-gray">
        <li>
            <a href="{{ route('home') }}" class="hover:text-bebas-blue focus:outline-none focus:ring-2 focus:ring-bebas-blue">Beranda</a>
        </li>
        @foreach ($items as $item)
            <li aria-hidden="true">/</li>
            <li>
                @if (! $loop->last && filled($item['url'] ?? null))
                    <a href="{{ $item['url'] }}" class="hover:text-bebas-blue focus:outline-none focus:ring-2 focus:ring-bebas-blue">{{ $item['label'] }}</a>
                @else
                    <span class="font-medium text-bebas-navy" aria-current="page">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
