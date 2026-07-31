# Release Checklist

Gunakan checklist ini sebelum dan sesudah deploy production.

## Local

- [ ] `composer install` berhasil.
- [ ] `npm ci` berhasil.
- [ ] `php artisan optimize:clear` berhasil.
- [ ] `php artisan migrate:status` tidak memiliki migration pending.
- [ ] `php artisan test` lulus.
- [ ] `vendor/bin/pint --test` lulus.
- [ ] `npm run build` lulus.
- [ ] `public/build/manifest.json` ada.
- [ ] `public/hot` tidak ada.
- [ ] `composer audit` tidak menemukan advisory.
- [ ] `npm audit --audit-level=moderate` tidak menemukan vulnerability.

## Production Environment

- [ ] `APP_ENV=production`.
- [ ] `APP_DEBUG=false`.
- [ ] `APP_URL=https://DOMAIN_ANDA`.
- [ ] `APP_KEY` sudah dibuat dan tidak berubah dari production aktif.
- [ ] Database production sudah dibuat.
- [ ] User database khusus sudah diberi privilege.
- [ ] `.env` tidak berada di document root.
- [ ] Document root mengarah ke folder `public`.
- [ ] SSL aktif.

## Deployment

- [ ] Backup database dibuat.
- [ ] Backup media dibuat.
- [ ] Maintenance mode dipakai jika deploy berisiko.
- [ ] File release terupload lengkap.
- [ ] `.env` production terpasang.
- [ ] `composer install --no-dev --optimize-autoloader` berhasil jika Composer tersedia.
- [ ] `php artisan migrate --force` berhasil.
- [ ] `php artisan storage:link` berhasil atau alternatif symlink tersedia.
- [ ] Folder `storage` writable.
- [ ] Folder `bootstrap/cache` writable.
- [ ] Cache production dibuat.
- [ ] Maintenance mode dimatikan.

## Smoke Test

- [ ] `/` status 200.
- [ ] `/berita` status 200.
- [ ] `/cari?q=berita` status 200.
- [ ] `/sitemap.xml` status 200 dan XML valid.
- [ ] `/feed` status 200 dan RSS valid.
- [ ] `/robots.txt` status 200.
- [ ] `/admin/login` status 200.
- [ ] Admin dapat login.
- [ ] Admin dapat membuat draft.
- [ ] Upload featured image berhasil.
- [ ] Gambar publik tampil.
- [ ] Redirect slug lama bekerja.
- [ ] Security headers HTML tersedia.
- [ ] Tidak ada mixed content.
- [ ] Log tidak berisi error kritis baru.

## Rollback

- [ ] Archive release sebelumnya tersedia.
- [ ] Backup database tersedia.
- [ ] Backup media tersedia.
- [ ] Langkah rollback sudah dibaca.
- [ ] Penanggung jawab rollback jelas.
