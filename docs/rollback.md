# Rollback Plan

Rollback hanya dilakukan setelah penyebab gangguan jelas dan backup tersedia. Jangan menjalankan command destructive tanpa backup production yang sudah diverifikasi.

## Kondisi Rollback

Pertimbangkan rollback jika:

- Halaman publik utama gagal dibuka.
- Login admin gagal untuk seluruh admin.
- Migration menimbulkan error data atau schema.
- Upload media rusak setelah deploy.
- Error 500 meningkat dan tidak dapat diperbaiki cepat.
- SEO endpoint penting seperti sitemap atau robots rusak setelah release.

## Backup Sebelum Rollback

Sebelum rollback, simpan kondisi saat ini:

- Export database production terbaru.
- Salin `storage/app/public`.
- Salin `.env` production.
- Salin `storage/logs/laravel.log`.
- Catat waktu rollback dan release yang sedang aktif.

Jangan menyimpan backup di folder publik.

## Code Rollback

Jika menggunakan folder release:

```bash
cd /home/CPANEL_USER
ln -sfn releases/RELEASE_SEBELUMNYA current
cd current
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Jika menggunakan upload folder biasa:

1. Aktifkan maintenance mode.
2. Rename folder aplikasi saat ini menjadi backup sementara.
3. Extract archive release sebelumnya ke folder aplikasi.
4. Kembalikan `.env` production.
5. Pastikan `storage` dan `public/storage` benar.
6. Jalankan cache production.
7. Nonaktifkan maintenance mode.

Command:

```bash
php artisan down
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan up
```

## Database Rollback

Jangan otomatis menjalankan:

```bash
php artisan migrate:rollback --force
```

Gunakan hanya jika migration yang baru benar-benar reversible, dampaknya dipahami, dan backup sudah ada.

Pendekatan aman:

1. Cek migration yang baru berjalan.
2. Cek apakah rollback menghapus kolom/tabel yang berisi data baru.
3. Jika data production perlu dipulihkan, restore database dari backup melalui phpMyAdmin atau command MySQL.
4. Verifikasi jumlah tabel dan data penting.

## Media Rollback

Database menyimpan path media, jadi file upload harus konsisten.

Langkah:

1. Jangan hapus media baru sampai dampak diketahui.
2. Jika restore database lama, restore juga `storage/app/public` dari waktu backup yang sama.
3. Pastikan `public/storage` tetap menunjuk ke `storage/app/public`.
4. Buka beberapa gambar featured dari halaman publik.

## Verification

Setelah rollback:

- `/` status 200.
- `/berita` status 200.
- `/admin/login` status 200.
- Admin dapat login.
- Gambar publik tampil.
- `/sitemap.xml`, `/feed`, dan `/robots.txt` benar.
- `storage/logs/laravel.log` tidak berisi error kritis baru.
- `php artisan migrate:status` sesuai release aktif.
