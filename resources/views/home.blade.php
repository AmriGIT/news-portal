<x-layouts.public :seo="$seo" :structured-data="$structuredData ?? []">
    <div class="mx-auto max-w-[1140px] px-4 py-8 md:px-6 lg:py-10">
        @if ($heroPost)
            <section aria-labelledby="berita-utama">
                <h2 id="berita-utama" class="sr-only">Berita Utama</h2>
                <x-public.featured-post-card :post="$heroPost" />
            </section>
        @else
            <x-public.empty-state title="Belum ada berita terbit" message="Berita utama akan tampil setelah redaksi menerbitkan konten." />
        @endif

        @if ($featuredPosts->isNotEmpty())
            <section class="mt-12" aria-labelledby="berita-pilihan">
                <x-public.section-heading title="Berita Pilihan" />
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($featuredPosts as $post)
                        <x-public.post-card :post="$post" heading-level="3" />
                    @endforeach
                </div>
            </section>
        @endif

        <section class="mt-12" aria-labelledby="berita-terbaru">
            <x-public.section-heading title="Berita Terbaru" :url="route('posts.index')" />
            @if ($latestPosts->isNotEmpty())
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($latestPosts as $post)
                        <x-public.post-card :post="$post" heading-level="3" />
                    @endforeach
                </div>
            @else
                <x-public.empty-state />
            @endif
        </section>

        @foreach ($categorySections as $section)
            <section class="mt-12" aria-labelledby="kategori-{{ $section['category']->slug }}">
                <x-public.section-heading
                    :title="$section['category']->name"
                    :url="route('categories.show', $section['category']->slug)"
                    link-label="Buka kategori"
                />
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($section['posts'] as $post)
                        <x-public.post-card :post="$post" heading-level="3" :show-excerpt="false" />
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-layouts.public>
