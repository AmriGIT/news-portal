<x-layouts.public :seo="$seo" :structured-data="$structuredData ?? []">
    <x-public.content-with-sidebar>
        <x-slot:sidebar>
            <x-public.sidebar :popular-posts="$sidebarPopularPosts" :tags="$sidebarTags" />
        </x-slot:sidebar>

        <article>
            <x-public.breadcrumb :items="array_slice($breadcrumbs ?? [], 1)" />

            <header>
                @if ($post->category)
                    <a href="{{ route('categories.show', $post->category->slug) }}" class="text-sm font-semibold uppercase tracking-normal text-bebas-blue hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-bebas-blue">{{ $post->category->name }}</a>
                @endif
                <h1 class="mt-3 text-3xl font-bold leading-tight text-bebas-navy sm:text-4xl lg:text-5xl">{{ $post->title }}</h1>
                @if (filled($post->excerpt))
                    <p class="mt-5 text-lg leading-8 text-slate-600">{{ $post->excerpt }}</p>
                @endif
                <div class="mt-5 flex flex-wrap gap-3 text-sm text-bebas-gray">
                    <span>Oleh {{ $post->author?->name ?? 'Redaksi' }}</span>
                    @if ($post->published_at)
                        <time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->timezone(config('app.timezone'))->translatedFormat('d F Y, H.i') }} WIB</time>
                    @endif
                </div>
            </header>

            @php
                $detailImages = $post->detailImagesForDisplay();
            @endphp

            <figure class="mt-8">
                @if (count($detailImages) > 1)
                    <div class="grid gap-3">
                        @foreach ($detailImages as $imageIndex => $imagePath)
                            <x-public.responsive-image
                                :path="$imagePath"
                                :alt="$imageIndex === 0 ? ($post->featured_image_alt ?: $post->title) : (($post->featured_image_alt ?: $post->title).' - gambar '.($imageIndex + 1))"
                                class="aspect-video w-full rounded-sm object-cover"
                                sizes="(min-width: 1024px) 896px, 100vw"
                                :loading="$imageIndex === 0 ? 'eager' : 'lazy'"
                                :fetchpriority="$imageIndex === 0 ? 'high' : 'auto'"
                                width="1600"
                                height="900"
                            />
                        @endforeach
                    </div>
                @else
                    <x-public.responsive-image
                        :path="$detailImages[0] ?? null"
                        :alt="$post->featured_image_alt ?: $post->title"
                        class="aspect-video w-full rounded-sm object-cover"
                        sizes="(min-width: 1024px) 896px, 100vw"
                        loading="eager"
                        fetchpriority="high"
                        width="1600"
                        height="900"
                    />
                @endif
                @if (filled($post->featured_image_caption) || filled($post->featured_image_credit))
                    <figcaption class="mt-3 text-sm leading-6 text-bebas-gray">
                        @if (filled($post->featured_image_caption))
                            <span>{{ $post->featured_image_caption }}</span>
                        @endif
                        @if (filled($post->featured_image_credit))
                            <span class="block">Kredit: {{ $post->featured_image_credit }}</span>
                        @endif
                    </figcaption>
                @endif
            </figure>

            <div class="rich-content mt-10" data-in-article-ads>
                {{-- Konten raw aman dirender karena sudah disanitasi saat disimpan di backend. --}}
                {!! $post->content !!}
            </div>

            @if ($post->tags->isNotEmpty())
                <footer class="mt-10 border-t border-slate-200 pt-6">
                    <p class="mb-3 text-sm font-semibold text-bebas-navy">Tag</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($post->tags as $tag)
                            <a href="{{ route('tags.show', $tag->slug) }}" class="rounded-sm border border-slate-300 px-3 py-1 text-sm text-slate-700 hover:border-bebas-blue hover:text-bebas-blue focus:outline-none focus:ring-2 focus:ring-bebas-blue">{{ $tag->name }}</a>
                        @endforeach
                    </div>
                </footer>
            @endif
        </article>
    </x-public.content-with-sidebar>

    @if ($relatedPosts->isNotEmpty())
        <section class="mx-auto max-w-[1400px] px-4 pb-12 md:px-6" aria-labelledby="berita-terkait">
            <x-public.section-heading title="Berita Terkait" />
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($relatedPosts as $relatedPost)
                    <x-public.post-card :post="$relatedPost" heading-level="3" :show-excerpt="false" />
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.public>
