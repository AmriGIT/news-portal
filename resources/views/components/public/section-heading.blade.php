@props(['title', 'url' => null, 'linkLabel' => 'Lihat semua'])

<div class="mb-6 flex items-center justify-between gap-4 border-b border-slate-200 pb-3">
    <h2 class="text-xl font-bold text-bebas-navy sm:text-2xl">{{ $title }}</h2>
    @if ($url)
        <a href="{{ $url }}" class="shrink-0 rounded-sm border border-bebas-blue px-3 py-2 text-sm font-semibold text-bebas-blue hover:bg-bebas-blue hover:text-white focus:outline-none focus:ring-2 focus:ring-bebas-blue focus:ring-offset-2">{{ $linkLabel }}</a>
    @endif
</div>
