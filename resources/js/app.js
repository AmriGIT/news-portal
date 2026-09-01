
function popupAdSelectors() {
    return [
        '.adsbygoogle-noablate',
        '[data-anchor-status]',
        '[aria-label="Advertisement"]',
        '[aria-label="Iklan"]',
        '[id^="google_ads_"]',
        '[id^="aswift_"]',
        'iframe[src*="googleads"]',
        'iframe[src*="googlesyndication"]',
    ];
}

function isOfficialAdSlot(element) {
    return Boolean(element.closest('.ad-slot, .in-article-ad'));
}

function isGoogleAdElement(element) {
    return element.matches('ins.adsbygoogle, .adsbygoogle, .adsbygoogle-noablate, [id^="google_ads_"], [id^="aswift_"], [data-anchor-status]') ||
        Boolean(element.querySelector('ins.adsbygoogle, .adsbygoogle, iframe[src*="googleads"], iframe[src*="googlesyndication"]'));
}

function isPopupLikeElement(element, header) {
    if (isOfficialAdSlot(element) || ! isGoogleAdElement(element)) {
        return false;
    }

    if (element.matches('.adsbygoogle-noablate, [data-anchor-status]')) {
        return true;
    }

    const style = window.getComputedStyle(element);
    const rect = element.getBoundingClientRect();
    const headerRect = header.getBoundingClientRect();
    const zIndex = Number.parseInt(style.zIndex, 10) || 0;
    const isOverlayPosition = ['fixed', 'sticky'].includes(style.position);
    const touchesHeader = rect.bottom > headerRect.top && rect.top < headerRect.bottom + 16;
    const coversViewportEdge = rect.top <= 8 || rect.bottom >= window.innerHeight - 8;

    return isOverlayPosition && (zIndex >= 100 || touchesHeader || coversViewportEdge);
}

function popupContainerFor(element) {
    return element.closest('.adsbygoogle-noablate, [data-anchor-status]') || element;
}

function resetInjectedTopSpacing(header) {
    const maxExpectedTopSpace = header.offsetHeight + 12;
    const hasAnchorPopup = document.querySelector('.adsbygoogle-noablate, [data-anchor-status]');

    if (! hasAnchorPopup) {
        return;
    }

    [document.documentElement, document.body].forEach((element) => {
        const topPadding = parseFloat(window.getComputedStyle(element).paddingTop) || 0;
        const topMargin = parseFloat(window.getComputedStyle(element).marginTop) || 0;

        if (topPadding > maxExpectedTopSpace) {
            element.style.setProperty('padding-top', '0px', 'important');
        }

        if (topMargin > maxExpectedTopSpace) {
            element.style.setProperty('margin-top', '0px', 'important');
        }
    });
}

function removeAdPopups() {
    const header = document.querySelector('[data-no-ads]');

    if (! header) {
        return;
    }

    resetInjectedTopSpacing(header);

    document.querySelectorAll(popupAdSelectors().join(',')).forEach((element) => {
        const container = popupContainerFor(element);

        if (isPopupLikeElement(container, header)) {
            container.remove();
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const cleanupAdPopups = () => window.requestAnimationFrame(removeAdPopups);

    removeAdPopups();
    window.addEventListener('load', cleanupAdPopups, { once: true });
    window.addEventListener('resize', cleanupAdPopups);
    [250, 750, 1500, 3000, 5000].forEach((delay) => window.setTimeout(removeAdPopups, delay));

    if (! window.MutationObserver) {
        return;
    }

    const observer = new MutationObserver(cleanupAdPopups);
    observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['style', 'class'] });
});

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

