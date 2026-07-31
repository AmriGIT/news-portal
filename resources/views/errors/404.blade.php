<x-layouts.public>
    <div class="mx-auto max-w-3xl px-4 py-20 text-center sm:px-6 lg:px-8">
        <p class="text-sm font-semibold uppercase tracking-normal text-bebas-blue">404</p>
        <h1 class="mt-3 text-4xl font-bold text-bebas-navy">Halaman tidak ditemukan</h1>
        <p class="mt-4 text-base leading-7 text-slate-600">Halaman yang Anda cari mungkin sudah dipindahkan, belum tersedia, atau tidak pernah dipublikasikan.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('home') }}" class="rounded-sm bg-bebas-blue px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-bebas-blue focus:ring-offset-2">Ke Beranda</a>
            <a href="{{ route('posts.index') }}" class="rounded-sm border border-slate-300 px-4 py-2 text-sm font-semibold text-bebas-navy hover:border-bebas-blue hover:text-bebas-blue focus:outline-none focus:ring-2 focus:ring-bebas-blue focus:ring-offset-2">Lihat Berita</a>
        </div>
    </div>
</x-layouts.public>
