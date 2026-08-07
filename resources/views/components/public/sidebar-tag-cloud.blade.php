@props(['tags'])

@php
    $maxCount = $tags->max('posts_count') ?: 1;
@endphp

<div class="sidebar-widget">
    <h3 class="sidebar-widget__title">Tag Populer</h3>
    <div class="tag-cloud">
        @foreach ($tags as $tag)
            @php
                $ratio = $tag->posts_count / $maxCount;
                $sizeClass = match (true) {
                    $ratio >= 0.8 => 'tag-cloud__item--xl',
                    $ratio >= 0.5 => 'tag-cloud__item--lg',
                    $ratio >= 0.25 => 'tag-cloud__item--md',
                    default => 'tag-cloud__item--sm',
                };
            @endphp
            <a
                href="{{ route('tags.show', $tag->slug) }}"
                class="tag-cloud__item {{ $sizeClass }}"
                title="{{ $tag->posts_count }} berita"
            >{{ $tag->name }}</a>
        @endforeach
    </div>
</div>
