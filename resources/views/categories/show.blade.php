<x-layouts.public :seo="$seo" :structured-data="$structuredData ?? []">
    <div class="mx-auto max-w-[1140px] px-4 py-8 md:px-6 lg:py-10">
        <x-public.breadcrumb :items="array_slice($breadcrumbs ?? [], 1)" />

        <header class="mb-8">
            <h1 class="text-3xl font-bold text-bebas-navy sm:text-4xl">{{ $category->name }}</h1>
            @if (filled($category->description))
                <p class="mt-3 max-w-2xl text-base leading-7 text-slate-600">{{ $category->description }}</p>
            @endif
        </header>

        @if ($posts->isNotEmpty())
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <x-public.post-card :post="$post" />
                @endforeach
            </div>

            <div class="mt-10">
                {{ $posts->links() }}
            </div>
        @else
            <x-public.empty-state title="Belum ada berita di kategori ini" />
        @endif
    </div>
</x-layouts.public>
