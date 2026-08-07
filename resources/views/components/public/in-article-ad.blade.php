@php
    $adsenseClient = config('services.adsense.client_id');
    $adsenseSlot = config('services.adsense.in_article_slot');
@endphp

<div class="in-article-ad" role="complementary" aria-label="Iklan">
    @if (filled($adsenseClient) && filled($adsenseSlot))
        <ins class="adsbygoogle"
             style="display:block; text-align:center;"
             data-ad-layout="in-article"
             data-ad-format="fluid"
             data-ad-client="{{ $adsenseClient }}"
             data-ad-slot="{{ $adsenseSlot }}"></ins>
        <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
    @else
        {{-- Placeholder --}}
        <div class="in-article-ad__placeholder">
            <span>Iklan In-Article</span>
        </div>
    @endif
</div>
