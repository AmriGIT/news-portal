# Solusi Masalah Upload Folder Storage & Symlink di cPanel

Dokumen teknis ini menjelaskan cara menangani folder `storage` yang tidak bisa di-upload ke cPanel karena berupa **Symlink (shortcut)** di komputer lokal.

---

## 🔍 Mengapa Folder `public/storage` Tidak Bisa Di-upload?

Di Laravel, folder `public/storage` di komputer lokal bukanlah folder biasa, melainkan **Symbolic Link (Symlink)** yang mengarah ke `storage/app/public/`. 

Sebagian besar program FTP dan File Manager cPanel menolak atau tidak mengizinkan pengunggahan file berbentuk symlink.

---

## 🛠️ Langkah Teknis Penyelesaian di cPanel

### 1. Upload File Gambar Asli
Upload semua file/folder media dari direktori komputer lokal Anda:
`d:\amri\myweb\blogger\news-portal\storage\app\public\`

Ke dalam direktori cPanel Anda:
📁 `/home/username/news-portal/storage/app/public/`

> ⚠️ *Jangan buat folder bernama `storage` secara manual di dalam folder `public_html/`.*

---

### 2. Buat Symlink Otomatis Menggunakan Script PHP

Jika hosting Anda tidak memiliki atau membatasi fitur **Terminal SSH**, buat file script PHP di `public_html`:

1. Buka cPanel **File Manager** → masuk ke folder **`public_html/`**.
2. Buat file baru: **`buat_symlink.php`**.
3. Salin dan tempel kode berikut:

```php
<?php
/**
 * Script Pembuat Symlink Storage Laravel di cPanel
 *
 * Ganti 'username' di bawah ini dengan nama user cPanel Anda.
 */

$cpanelUsername = 'username'; // <-- SESUAIKAN DENGAN USERNAME CPANEL ANDA

$target   = "/home/{$cpanelUsername}/news-portal/storage/app/public";
$shortcut = "/home/{$cpanelUsername}/public_html/storage";

// Hapus symlink / shortcut lama jika sudah ada
if (is_link($shortcut) || file_exists($shortcut)) {
    @unlink($shortcut);
}

// Eksekusi pembuat symlink
if (symlink($target, $shortcut)) {
    echo "<div style='font-family:sans-serif; padding:20px; background:#dcfce7; color:#166534; border-radius:8px;'>";
    echo "<h2>✅ BERHASIL!</h2>";
    echo "<p>Symlink storage berhasil dibuat dari <strong>{$target}</strong> ke <strong>{$shortcut}</strong>.</p>";
    echo "<p><em>Silakan hapus file buat_symlink.php ini demi keamanan server Anda.</em></p>";
    echo "</div>";
} else {
    echo "<div style='font-family:sans-serif; padding:20px; background:#fee2e2; color:#991b1b; border-radius:8px;'>";
    echo "<h2>❌ GAGAL MEMBUAT SYMLINK</h2>";
    echo "<p>Periksa apakah nama username cPanel (<strong>{$cpanelUsername}</strong>) dan path folder sudah benar.</p>";
    echo "</div>";
}
```

4. Simpan file tersebut.
5. Jalankan script melalui browser dengan mengakses:
   `https://domain-anda.com/buat_symlink.php`

6. Setelah muncul notifikasi **BERHASIL**, segera **HAPUS** file `buat_symlink.php`.

---

### 3. Alternatif Menggunakan Terminal cPanel (Jika Tersedia)

Jika cPanel Anda memiliki fitur **Terminal**, Anda tidak perlu membuat script PHP. Cukup jalankan perintah berikut di Terminal:

```bash
ln -s /home/username/news-portal/storage/app/public /home/username/public_html/storage
```

---

## 🔒 Permission Folder
Pastikan folder `news-portal/storage/` dan subfoldernya memiliki permission `755` atau `775` di File Manager agar gambar dapat dibaca oleh web server.
