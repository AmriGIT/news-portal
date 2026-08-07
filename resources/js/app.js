document.addEventListener('DOMContentLoaded', () => {
    const button = document.querySelector('[data-mobile-menu-button]');
    const menu = document.querySelector('[data-mobile-menu]');

    if (! button || ! menu) {
        return;
    }

    button.addEventListener('click', () => {
        const expanded = button.getAttribute('aria-expanded') === 'true';

        button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        menu.classList.toggle('hidden', expanded);
    });
});

/**
 * In-Article Ad Injection
 *
 * Sisipkan iklan in-article setelah paragraf ke-3 dan ke-7
 * pada konten artikel (.rich-content[data-in-article-ads]).
 */
document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('.rich-content[data-in-article-ads]');

    if (! container) {
        return;
    }

    const paragraphs = container.querySelectorAll(':scope > p');
    const insertAfter = [2, 6]; // 0-indexed: after 3rd (index 2) and 7th (index 6)

    insertAfter.forEach((index) => {
        if (paragraphs.length <= index) {
            return;
        }

        const adElement = document.createElement('div');
        adElement.className = 'in-article-ad';
        adElement.setAttribute('role', 'complementary');
        adElement.setAttribute('aria-label', 'Iklan');

        // Fetch AdSense Client & Slot ID from meta tags or script src
        const adsenseClient = document.querySelector('meta[name="adsense-client-id"]')?.content ||
            document.querySelector('script[src*="adsbygoogle"]')?.src?.match(/client=([^&]+)/)?.[1];
        const adsenseSlot = document.querySelector('meta[name="adsense-in-article-slot"]')?.content ||
            container.getAttribute('data-in-article-slot');

        if (adsenseClient && adsenseSlot) {
            adElement.innerHTML = `
                <ins class="adsbygoogle"
                     style="display:block; text-align:center;"
                     data-ad-layout="in-article"
                     data-ad-format="fluid"
                     data-ad-client="${adsenseClient}"
                     data-ad-slot="${adsenseSlot}"></ins>
            `;
            paragraphs[index].after(adElement);
            (window.adsbygoogle = window.adsbygoogle || []).push({});
            return;
        }

        // Fallback: placeholder if AdSense is not configured
        adElement.innerHTML = '<div class="in-article-ad__placeholder"><span>Iklan In-Article</span></div>';
        paragraphs[index].after(adElement);
    });
});
