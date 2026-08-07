<x-layouts.public :seo="$seo" :structured-data="$structuredData ?? []">
    <div class="mx-auto max-w-[1400px] px-4 py-8 md:px-6 lg:py-10">
        <x-public.breadcrumb :items="array_slice($breadcrumbs ?? [], 1)" />

        <header class="mb-8">
            <h1 class="text-3xl font-bold text-bebas-navy sm:text-4xl">Pencarian Berita</h1>
            @if (filled($keyword))
                <p class="mt-3 max-w-2xl text-base leading-7 text-slate-600">
                    Hasil pencarian untuk <span class="font-semibold text-bebas-navy">{{ $keyword }}</span>.
                </p>
            @else
                <p class="mt-3 max-w-2xl text-base leading-7 text-slate-600">Masukkan kata kunci untuk menemukan berita.</p>
            @endif
        </header>

        <form action="{{ route('search') }}" method="GET" class="mb-8 max-w-2xl" role="search">
            <label for="search-page-query" class="sr-only">Kata kunci pencarian</label>
            <div class="flex overflow-hidden rounded-sm border border-slate-300 bg-white focus-within:border-bebas-blue focus-within:ring-2 focus-within:ring-bebas-blue">
                <input
                    id="search-page-query"
                    name="q"
                    type="search"
                    value="{{ old('q', $keyword) }}"
                    minlength="2"
                    maxlength="100"
                    placeholder="Cari judul, ringkasan, isi, atau kategori"
                    class="min-w-0 flex-1 border-0 bg-transparent px-4 py-3 text-base text-bebas-navy outline-none placeholder:text-bebas-gray"
                    autofocus
                >
                <button type="submit" class="shrink-0 bg-bebas-blue px-5 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-bebas-blue focus:ring-offset-2">
                    Cari
                </button>
            </div>
            @error('q')
                <p class="mt-2 text-sm text-bebas-red">{{ $message }}</p>
            @enderror
        </form>

        @if (filled($keyword))
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
                <x-public.empty-state title="Tidak ada hasil" message="Coba gunakan kata kunci lain yang lebih umum." />
            @endif
        @endif
    </div>
</x-layouts.public>
