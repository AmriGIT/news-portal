@props(['posts'])

<div class="sidebar-widget">
    <h3 class="sidebar-widget__title">Berita Populer</h3>
    <ol class="sidebar-popular-list">
        @foreach ($posts as $index => $post)
            <li class="sidebar-popular-item">
                <a href="{{ route('posts.show', $post->slug) }}" class="sidebar-popular-link group">
                    <span class="sidebar-popular-number" aria-hidden="true">{{ $index + 1 }}</span>
                    <div class="sidebar-popular-content">
                        @if (filled($post->featured_image))
                            <x-public.responsive-image
                                :path="$post->featured_image"
                                :alt="$post->featured_image_alt ?: $post->title"
                                class="sidebar-popular-thumb"
                                sizes="64px"
                                loading="lazy"
                                width="64"
                                height="64"
                            />
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="sidebar-popular-title group-hover:text-bebas-blue">{{ $post->title }}</p>
                            @if ($post->published_at)
                                <time datetime="{{ $post->published_at->toIso8601String() }}" class="sidebar-popular-date">
                                    {{ $post->published_at->timezone(config('app.timezone'))->translatedFormat('d M Y') }}
                                </time>
                            @endif
                        </div>
                    </div>
                </a>
            </li>
        @endforeach
    </ol>
</div>
