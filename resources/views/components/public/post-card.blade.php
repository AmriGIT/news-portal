@props([
    'post',
    'headingLevel' => 2,
    'showExcerpt' => true,
    'showCategory' => true,
])

@php
    $heading = in_array((int) $headingLevel, [2, 3, 4], true) ? (int) $headingLevel : 2;
    $date = $post->published_at?->timezone(config('app.timezone'))->translatedFormat('d F Y');
    $author = $post->author?->name ?? 'Admin';
@endphp

<article class="group flex h-full flex-col overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <a href="{{ route('posts.show', $post->slug) }}" class="block overflow-hidden focus:outline-none focus:ring-2 focus:ring-bebas-blue focus:ring-offset-2">
        <x-public.responsive-image
            :path="$post->featured_image"
            :alt="$post->featured_image_alt ?: $post->title"
            class="aspect-video w-full object-cover transition duration-200 group-hover:scale-[1.02]"
            sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw"
            loading="lazy"
            width="960"
            height="540"
        />
    </a>

    <div class="flex flex-1 flex-col p-4">
        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-normal text-bebas-gray">
            @if ($showCategory && $post->category)
                <a href="{{ route('categories.show', $post->category->slug) }}" class="text-bebas-blue hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-bebas-blue">{{ $post->category->name }}</a>
            @endif
            @if ($date)
                <time datetime="{{ $post->published_at?->toDateString() }}">{{ $date }}</time>
            @endif
        </div>

        @if ($heading === 3)
            <h3 class="mt-2 text-lg font-bold leading-snug text-bebas-navy">
                <a href="{{ route('posts.show', $post->slug) }}" class="hover:text-bebas-blue focus:outline-none focus:ring-2 focus:ring-bebas-blue">{{ $post->title }}</a>
            </h3>
        @elseif ($heading === 4)
            <h4 class="mt-2 text-base font-bold leading-snug text-bebas-navy">
                <a href="{{ route('posts.show', $post->slug) }}" class="hover:text-bebas-blue focus:outline-none focus:ring-2 focus:ring-bebas-blue">{{ $post->title }}</a>
            </h4>
        @else
            <h2 class="mt-2 text-xl font-bold leading-snug text-bebas-navy">
                <a href="{{ route('posts.show', $post->slug) }}" class="hover:text-bebas-blue focus:outline-none focus:ring-2 focus:ring-bebas-blue">{{ $post->title }}</a>
            </h2>
        @endif

        @if ($showExcerpt && filled($post->excerpt))
            <p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-600">{{ $post->excerpt }}</p>
        @endif

        <div class="mt-auto pt-4 text-xs text-bebas-gray">Oleh {{ $author }}</div>
    </div>
</article>
