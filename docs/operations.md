# Operations Checklist

Dokumen ini untuk operasional rutin setelah portal berita go-live.

## Backup Schedule

Harian:

- Backup database.
- Backup `storage/app/public`.
- Simpan backup di lokasi non-public.

Mingguan:

- Download salinan backup ke lokasi terpisah.
- Cek ukuran backup masuk akal.
- Hapus backup lama sesuai kebijakan retensi.

Bulanan:

- Test restore di staging atau lokal.
- Pastikan database dan media bisa dipulihkan bersama.

## Log

Cek berkala:

```text
storage/logs/laravel.log
```

Yang perlu ditindak:

- Error 500 berulang.
- SQL error.
- Permission denied.
- Upload failure.
- Mail failure.
- Scheduler failure.

Jangan membagikan log mentah jika berisi path server, email user, atau detail sensitif.

## Cron

Jika cron `schedule:run` diaktifkan:

- Cek timestamp log scheduler.
- Pastikan command memakai binary PHP versi benar.
- Jangan menjalankan scheduler melalui route publik.
- Jika hosting minimum cron 5 menit, dokumentasikan potensi keterlambatan task.

## Disk

Pantau:

- `storage/app/public`
- `storage/logs`
- `storage/framework/cache`
- Backup database

Jangan menghapus media konten secara otomatis tanpa tracking yang aman.

## User Management

- Buat admin production melalui `php artisan admin:create`.
- Nonaktifkan user yang tidak lagi bekerja.
- Jangan berbagi akun admin.
- Gunakan password kuat dan unik.
- Editor hanya mengelola berita miliknya sesuai policy.

## Dependency Update

Jangan update dependency langsung di production.

Alur aman:

```bash
composer update
npm update
php artisan test
vendor/bin/pint --test
npm run build
```

Lakukan di lokal atau staging, lalu deploy release baru.

## Security Review

Bulanan:

```bash
composer audit
npm audit --audit-level=moderate
```

Cek juga:

- `APP_DEBUG=false`.
- Document root hanya folder `public`.
- `.env` tidak dapat diakses publik.
- `public/hot` tidak ada.
- Permission tidak memakai `777`.
- SSL aktif dan tidak expired.
- `/robots.txt` sesuai environment.
- `/sitemap.xml` tidak memuat draft atau noindex.

## SEO Review

Mingguan:

- Buka `/sitemap.xml`.
- Buka `/feed`.
- Buka `/robots.txt`.
- Cek detail artikel baru punya canonical, Open Graph, dan JSON-LD.
- Pastikan `APP_URL` bukan localhost.

## Cache

Jika konten terlihat stale:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Cache publik konten dibersihkan otomatis saat `Post`, `Category`, atau `Tag` berubah.
