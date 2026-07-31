# Deployment Shared Hosting cPanel

Dokumen ini untuk deploy production portal berita Laravel ke shared hosting cPanel. Jangan menaruh credential nyata di repository, tiket publik, atau log.

## Requirement Server

- PHP 8.3 atau versi yang kompatibel dengan `composer.json`.
- Extension PHP: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd`, `iconv`, `intl`, `json`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`, dan `zip`.
- MySQL 8 atau MariaDB yang kompatibel dengan charset `utf8mb4`.
- Composer 2 di server, atau siapkan folder `vendor` dari environment Linux yang kompatibel.
- Node dan npm hanya diperlukan di local/CI untuk build asset. Jangan jalankan Vite dev server di production.

## Struktur Folder

Struktur yang direkomendasikan:

```text
/home/CPANEL_USER/
  portal-app/
    app/
    bootstrap/
    config/
    database/
    public/
    resources/
    routes/
    storage/
    vendor/
    artisan
    composer.json
    composer.lock
    .env
  public_html/ atau document root domain -> /home/CPANEL_USER/portal-app/public
```

Document root harus mengarah ke folder `public`. Jangan arahkan domain ke root Laravel.

## Environment Variable

Gunakan `.env.production.example` sebagai template. Isi credential langsung di file `.env` server melalui File Manager atau Terminal cPanel.

Nilai penting production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://DOMAIN_ANDA
APP_TIMEZONE=Asia/Jakarta
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=public
```

Jangan mengganti `APP_KEY` setelah aplikasi production berjalan dan menyimpan data terenkripsi.

## Perintah Local Final

Jalankan dari folder project lokal:

```bash
composer install
npm ci
php artisan optimize:clear
php artisan test
vendor/bin/pint --test
npm run build
php artisan route:list
php artisan migrate:status
```

Di Windows, gunakan:

```bash
vendor\bin\pint.bat --test
npm.cmd run build
```

Pastikan `public/build/manifest.json` tersedia dan `public/hot` tidak ada.

## File Deployment

Upload:

```text
app/
bootstrap/
config/
database/
public/
resources/
routes/
storage/
vendor/ jika Composer tidak tersedia di server
artisan
composer.json
composer.lock
.env production di server saja
```

Jangan upload:

```text
.env local
.git/
node_modules/
tests/
phpunit.xml
storage/logs/*.log
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
public/hot
```

## Composer Install Production

Jika Composer tersedia di server:

```bash
cd /home/CPANEL_USER/portal-app
composer install --no-dev --optimize-autoloader
```

Jangan gunakan `--ignore-platform-reqs`. Jika gagal karena extension hilang, aktifkan extension melalui cPanel atau hubungi provider.

## Database Production

Di cPanel, buat database dan user khusus melalui MySQL Databases atau MySQL Database Wizard.

Checklist:

- Database memakai charset `utf8mb4`.
- User database diberi privilege yang diperlukan hanya untuk database portal.
- Nama database dan username biasanya memakai prefix akun cPanel.
- Backup database lama sebelum migration.

Jalankan migration production:

```bash
php artisan migrate --force
```

Jangan jalankan `migrate:fresh`, `db:wipe`, atau command lain yang menghapus data production.

## Admin Pertama

Jangan gunakan seeder development di production. Buat admin pertama lewat command:

```bash
php artisan admin:create
```

Command akan meminta nama, email, password, dan konfirmasi. Password tidak dicetak ke output.

Untuk automation tepercaya saja:

```bash
php artisan admin:create --name="NAMA_ADMIN" --email="EMAIL_ADMIN" --force
```

Biarkan password dimasukkan melalui prompt interaktif agar tidak tersimpan di shell history.

## Storage Link

Utama:

```bash
php artisan storage:link
```

Jika symlink tidak diizinkan:

- Coba fitur cPanel Terminal dengan command yang sama.
- Gunakan fitur symlink/File Manager jika tersedia.
- Hubungi provider agar `public/storage` menunjuk ke `storage/app/public`.
- Jangan menyalin file upload secara manual setiap deploy.

## Permission

Folder yang harus writable oleh user PHP:

```text
storage/
bootstrap/cache/
```

Gunakan permission konservatif seperti folder `755` dan file `644` jika ownership benar. Jangan gunakan `777`.

## Cache Production

Setelah `.env` benar:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Jika mengubah `.env`, jalankan ulang:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

## Cron Scheduler

Project belum mendaftarkan task scheduler khusus. Tetap boleh menyiapkan cron Laravel jika nanti task scheduler ditambahkan:

```cron
* * * * * cd /home/CPANEL_USER/portal-app && /path/to/php artisan schedule:run >> /home/CPANEL_USER/portal-app/storage/logs/scheduler.log 2>&1
```

Jika hosting hanya mengizinkan 5 menit:

```cron
*/5 * * * * cd /home/CPANEL_USER/portal-app && /path/to/php artisan schedule:run >> /home/CPANEL_USER/portal-app/storage/logs/scheduler.log 2>&1
```

Jangan membuat route publik untuk menjalankan scheduler.

## SSL dan HTTPS

- Aktifkan AutoSSL atau sertifikat valid di cPanel.
- Pastikan `APP_URL` memakai `https://`.
- Pastikan `SESSION_SECURE_COOKIE=true`.
- Buka halaman publik dan admin tanpa mixed content.
- Jangan aktifkan HSTS preload pada deployment pertama.

## Smoke Test

Cek halaman publik:

```text
/
/berita
/cari?q=berita
/sitemap.xml
/sitemaps/posts.xml
/feed
/robots.txt
/up
```

Cek admin:

```text
/admin/login
/admin
/admin/posts
/admin/manage-site-settings
```

Cek upload:

- Upload featured image valid.
- Pastikan varian WebP dibuat.
- Pastikan gambar dapat diakses lewat URL `/storage/...`.

## Common Error

`500` setelah deploy:

- Cek `storage/logs/laravel.log`.
- Jalankan `php artisan optimize:clear`.
- Pastikan `.env` lengkap.
- Pastikan `APP_KEY` ada.

`Vite manifest not found`:

- Jalankan `npm run build` lokal.
- Pastikan folder `public/build` ikut terupload.
- Hapus `public/hot` jika ada.

`SQLSTATE access denied`:

- Cek prefix nama database cPanel.
- Pastikan user database sudah ditambahkan ke database.
- Pastikan password database benar di `.env`.

`Storage image 404`:

- Jalankan `php artisan storage:link`.
- Pastikan `public/storage` menunjuk ke `storage/app/public`.
- Pastikan file upload benar-benar ada di disk production.

`419 Page Expired`:

- Pastikan session storage writable atau table `sessions` ada.
- Pastikan domain dan HTTPS cookie sesuai.
- Jangan menonaktifkan CSRF.
