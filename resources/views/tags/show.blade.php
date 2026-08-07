<x-layouts.public :seo="$seo" :structured-data="$structuredData ?? []">
    <x-public.content-with-sidebar>
        <x-slot:sidebar>
            <x-public.sidebar :popular-posts="$sidebarPopularPosts" :tags="$sidebarTags" />
        </x-slot:sidebar>

        <x-public.breadcrumb :items="array_slice($breadcrumbs ?? [], 1)" />

        <header class="mb-8">
            <h1 class="text-3xl font-bold text-bebas-navy sm:text-4xl">Tag: {{ $tag->name }}</h1>
            @if (filled($tag->description))
                <p class="mt-3 max-w-2xl text-base leading-7 text-slate-600">{{ $tag->description }}</p>
            @else
                <p class="mt-3 max-w-2xl text-base leading-7 text-slate-600">Kumpulan berita dengan tag {{ $tag->name }}.</p>
            @endif
        </header>

        @if ($posts->isNotEmpty())
            <div class="grid gap-5 sm:grid-cols-2">
                @foreach ($posts as $post)
                    <x-public.post-card :post="$post" />
                @endforeach
            </div>

            <div class="mt-10">
                {{ $posts->links() }}
            </div>
        @else
            <x-public.empty-state title="Belum ada berita dengan tag ini" />
        @endif
    </x-public.content-with-sidebar>
</x-layouts.public>
