# Panduan Deploy Laravel News Portal ke cPanel Hosting

Panduan lengkap langkah demi langkah untuk mengunggah dan mendesploi aplikasi **Laravel News Portal** ke **cPanel Shared Hosting** dengan aman.

---

## 🏗️ Struktur Direktori cPanel Terbaik & Aman

Untuk keamanan, kode aplikasi Laravel (logic & backend) diletakkan **di luar `public_html`**, sedangkan aset publik (`index.php`, `css`, `js`, `images`) diletakkan **di dalam `public_html`**.

```text
/home/username/
├── news-portal/                 <-- (Aplikasi Laravel: app, config, bootstrap, vendor, .env, dll)
└── public_html/                 <-- (Hanya isi folder public: index.php, build/, storage, .htaccess)
```

---

## 📦 Langkah 1: Persiapan di Komputer Lokal

Sebelum mengunggah, kompilasi aset frontend dan optimasi autoloader di komputer lokal:

1. **Kompilasi Aset Frontend (Vite & Tailwind CSS)**:
   ```bash
   npm run build
   ```

2. **Install Dependensi Production Composer**:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. **Kompres File Project menjadi ZIP**:
   Kompres seluruh isi folder `news-portal` menjadi `news-portal.zip`.
   > ⛔ **Jangan sertakan**: `.git/`, `node_modules/`, `tests/`.

---

## 📤 Langkah 2: Upload & Ekstrak di cPanel File Manager

1. Login ke **cPanel** → buka **File Manager**.
2. Masuk ke root directory `/home/username/` (sejajar dengan folder `public_html`).
3. Buat folder baru bernama `news-portal`.
4. **Upload** `news-portal.zip` ke dalam folder `/home/username/news-portal/`.
5. **Ekstrak** `news-portal.zip` di folder tersebut.

---

## 🌐 Langkah 3: Konfigurasi `public_html`

1. Pindahkan semua file dan folder dari `/home/username/news-portal/public/` ke dalam `/home/username/public_html/`.
   - Termasuk `.htaccess`, `index.php`, folder `build/`, `images/`, `favicon.png`, dll.

2. Buka file `/home/username/public_html/index.php` di File Manager (pilih **Edit**):
   Ubah path `autoload.php` dan `app.php` mengarah ke folder `news-portal`:

   ```php
   // UBAH BARIS 14 DARI:
   require __DIR__.'/../vendor/autoload.php';
   // MENJADI:
   require __DIR__.'/../news-portal/vendor/autoload.php';

   // UBAH BARIS 18 DARI:
   $app = require_once __DIR__.'/../bootstrap/app.php';
   // MENJADI:
   $app = require_once __DIR__.'/../news-portal/bootstrap/app.php';
   ```

---

## 🗄️ Langkah 4: Buat & Import Database MySQL

1. Di cPanel, buka **MySQL® Databases**:
   - Buat Database baru (misal: `user_newsportal`).
   - Buat User Database baru & Password (misal: `user_newsuser`).
   - Tambahkan User ke Database dan berikan **ALL PRIVILEGES**.

2. Import Database:
   - Di cPanel, buka **phpMyAdmin**.
   - Pilih database yang baru dibuat.
   - Klik **Import** → pilih file `.sql` export dari lokal Anda → klik **Go**.

---

## ⚙️ Langkah 5: Konfigurasi File `.env` di cPanel

1. Di File Manager, masuk ke `/home/username/news-portal/`.
2. Buat/Edit file `.env` dan sesuaikan nilainya untuk server produksi:

   ```env
   APP_NAME="BebasInfo"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://domain-anda.com

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=user_newsportal
   DB_USERNAME=user_newsuser
   DB_PASSWORD=password_db_anda

   ADSENSE_CLIENT_ID=ca-pub-XXXXXXXXXXXXXXXX
   ADSENSE_SIDEBAR_SLOT=1234567890
   ADSENSE_IN_ARTICLE_SLOT=0987654321
   ```

---

## 🔗 Langkah 6: Solusi Storage Gambar & Buat Symlink di cPanel

Folder `public/storage` di komputer lokal adalah **symlink (shortcut)** yang tidak bisa di-upload langsung via File Manager/FTP.

### A. Upload Gambar Asli
Upload isi file/folder gambar Anda dari lokal `storage/app/public/` ke direktori server:
📁 `/home/username/news-portal/storage/app/public/`

---

### B. Cara Membuat Symlink di cPanel

#### Opsi 1 (Menggunakan PHP Script — Tanpa Perlu Akses Terminal):
1. Masuk ke cPanel **File Manager** → buka folder **`public_html/`**.
2. Buat file baru bernama **`buat_symlink.php`**.
3. Isi dengan kode PHP berikut *(ganti `username` sesuai username cPanel Anda)*:

```php
<?php
// Ganti 'username' sesuai nama user cPanel Anda!
$target = '/home/username/news-portal/storage/app/public';
$shortcut = '/home/username/public_html/storage';

// Hapus folder/symlink lama jika ada
if (is_link($shortcut) || file_exists($shortcut)) {
    @unlink($shortcut);
}

// Buat symlink baru
if (symlink($target, $shortcut)) {
    echo '<h2>✅ BERHASIL! Symlink storage berhasil dibuat.</h2>';
} else {
    echo '<h2>❌ GAGAL membuat symlink. Periksa kembali username cPanel Anda.</h2>';
}
```

4. Simpan file, lalu akses via browser: `https://domain-anda.com/buat_symlink.php`.
5. Setelah muncul tulisan **BERHASIL**, segera **HAPUS** file `buat_symlink.php`.

---

#### Opsi 2 (Menggunakan Terminal cPanel):
Buka **Terminal** di cPanel dan jalankan:
```bash
ln -s /home/username/news-portal/storage/app/public /home/username/public_html/storage
```

---

## 🚀 Langkah 7: Cache & Optimasi (Selesai!)

Di **Terminal cPanel** (atau via fitur Cron Job):
```bash
cd /home/username/news-portal
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Atur juga hak akses permission folder:
- Folder `news-portal/storage` & `news-portal/bootstrap/cache` harus memiliki permission `755` atau `775`.

Website Laravel News Portal Anda sekarang sudah live & aktif secara aman di cPanel! 🎉

