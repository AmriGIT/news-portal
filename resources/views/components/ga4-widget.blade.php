<div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl p-6 shadow-lg" style="font-family: 'Inter', sans-serif;">
    <h2 class="text-xl font-semibold mb-2">Pengunjung GA4</h2>
    <p class="text-3xl font-bold" id="ga4-visitors">--</p>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        fetch('{{ route('admin.analytics.ga4') }}')
            .then(res => res.json())
            .then(data => {
                const el = document.getElementById('ga4-visitors');
                if (el) el.textContent = data.visitors;
            })
            .catch(err => console.error('GA4 widget error:', err));
    });
</script>
