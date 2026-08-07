@props(['sidebarClass' => ''])

<div class="mx-auto max-w-[1400px] px-4 py-8 md:px-6 lg:py-10">
    <div class="lg:flex lg:gap-8">
        {{-- Konten utama --}}
        <div class="min-w-0 flex-1">
            {{ $slot }}
        </div>

        {{-- Sidebar --}}
        <aside {{ $attributes->class(['w-full lg:w-[300px] lg:shrink-0', $sidebarClass]) }} aria-label="Sidebar">
            <div class="sidebar-sticky space-y-6 lg:sticky lg:top-24">
                {{ $sidebar }}
            </div>
        </aside>
    </div>
</div>
