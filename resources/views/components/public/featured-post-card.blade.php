@props(['post'])

@php
    $date = $post?->published_at?->timezone(config('app.timezone'))->translatedFormat('d F Y');
    $author = $post?->author?->name ?? 'Admin';
@endphp

@if ($post)
    <article class="group relative overflow-hidden rounded-md bg-bebas-navy shadow-sm">
        <a href="{{ route('posts.show', $post->slug) }}" class="block focus:outline-none focus:ring-2 focus:ring-bebas-blue focus:ring-offset-2">
            <x-public.responsive-image
                :path="$post->featured_image"
                :alt="$post->featured_image_alt ?: $post->title"
                class="aspect-video min-h-[300px] w-full object-cover transition duration-300 group-hover:scale-[1.02] sm:min-h-[360px]"
                sizes="(min-width: 1025px) 760px, 100vw"
                loading="eager"
                fetchpriority="high"
                width="1600"
                height="900"
            />
            <span class="absolute inset-0 bg-gradient-to-t from-bebas-navy/95 via-bebas-navy/45 to-transparent" aria-hidden="true"></span>
            <div class="absolute bottom-0 left-0 max-w-3xl p-4 sm:p-6 lg:p-8">
                @if ($post->category)
                    <span class="inline-flex rounded-sm bg-bebas-blue px-2 py-1 text-xs font-bold uppercase tracking-normal text-white">{{ $post->category->name }}</span>
                @endif
                <h1 class="mt-3 max-w-2xl text-2xl font-bold leading-tight text-white sm:text-3xl lg:text-4xl">{{ $post->title }}</h1>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-medium text-white/85">
                    @if ($date)
                        <time datetime="{{ $post->published_at?->toDateString() }}">{{ $date }}</time>
                        <span aria-hidden="true">-</span>
                    @endif
                    <span>{{ $author }}</span>
                </div>
                @if (filled($post->excerpt))
                    <p class="mt-4 hidden max-w-2xl text-sm leading-6 text-white/80 sm:block">{{ $post->excerpt }}</p>
                @endif
            </div>
        </a>
    </article>
@endif
