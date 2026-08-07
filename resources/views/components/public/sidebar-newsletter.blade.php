<div class="sidebar-widget sidebar-newsletter">
    <div class="sidebar-newsletter__inner">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="sidebar-newsletter__icon">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
        </svg>
        <h3 class="sidebar-newsletter__title">Langganan Berita</h3>
        <p class="sidebar-newsletter__desc">Dapatkan berita terbaru langsung di email Anda.</p>
        <form class="sidebar-newsletter__form" method="POST" action="#" onsubmit="event.preventDefault(); this.querySelector('.sidebar-newsletter__success').classList.remove('hidden'); this.querySelector('button').disabled = true;">
            <label for="sidebar-newsletter-email" class="sr-only">Alamat email</label>
            <input
                id="sidebar-newsletter-email"
                type="email"
                name="email"
                placeholder="Masukkan email Anda"
                required
                class="sidebar-newsletter__input"
            >
            <button type="submit" class="sidebar-newsletter__btn">
                Langganan
            </button>
            <p class="sidebar-newsletter__success hidden">
                ✓ Terima kasih! Anda akan menerima update berita terbaru.
            </p>
        </form>
    </div>
</div>
