@props([
    'slotId' => 'sidebar-ad',
    'format' => 'rectangle',
])

@php
    $adsenseClient = config('services.adsense.client_id');
    $adsenseSlot = config('services.adsense.sidebar_slot');
    $dimensions = match ($format) {
        'half-page' => ['width' => 300, 'height' => 600],
        default => ['width' => 300, 'height' => 250],
    };
@endphp

<div id="{{ $slotId }}" class="ad-slot" data-ad-format="{{ $format }}">
    @if (filled($adsenseClient) && filled($adsenseSlot))
        <ins class="adsbygoogle"
             style="display:block;width:{{ $dimensions['width'] }}px;height:{{ $dimensions['height'] }}px"
             data-ad-client="{{ $adsenseClient }}"
             data-ad-slot="{{ $adsenseSlot }}"
             data-ad-format="auto"
             data-full-width-responsive="true"></ins>
        <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
    @else
        {{-- Placeholder saat AdSense belum dikonfigurasi --}}
        <div class="ad-slot__placeholder" style="width:{{ $dimensions['width'] }}px;height:{{ $dimensions['height'] }}px">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="ad-slot__icon">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6V7.5Z" />
            </svg>
            <span>Ruang Iklan</span>
        </div>
    @endif
</div>
