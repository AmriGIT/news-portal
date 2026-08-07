# Panduan Konfigurasi Google AdSense (`setAdsense.md`)

Dokumen ini berisi panduan langkah demi langkah untuk mendapatkan kredensial **Google AdSense** dari Dashboard Google AdSense dan memasangnya ke dalam file `.env` project News Portal.

---

## 🔑 Variabel `.env` yang Dibutuhkan

Buka file `.env` di direktori `news-portal/` dan isi variabel berikut:

```env
ADSENSE_CLIENT_ID=ca-pub-XXXXXXXXXXXXXXXX
ADSENSE_SIDEBAR_SLOT=1234567890
ADSENSE_IN_ARTICLE_SLOT=0987654321
```

---

## 📋 Cara Mendapatkan Nilai dari Dashboard Google AdSense

### 1. `ADSENSE_CLIENT_ID` (Publisher ID)

**Lokasi Menu AdSense:**
1. Login ke [Dashboard Google AdSense](https://adsense.google.com).
2. Di menu samping kiri, klik **Akun** *(Account)* → **Setelan** *(Settings)* → **Informasi Akun** *(Account information)*.
3. Cari bidang **ID Publikasi** *(Publisher ID)*.

- **Format**: `ca-pub-XXXXXXXXXXXXXXXX`
- **Contoh**: `ca-pub-1234567890123456`

> 💡 *Tips:* ID ini juga dapat dilihat pada URL browser saat Anda login ke dashboard AdSense (misal `.../pub-1234567890123456/...`).

---

### 2. `ADSENSE_SIDEBAR_SLOT` (Unit Iklan Sidebar)

**Lokasi Menu AdSense:**
1. Login ke [Dashboard Google AdSense](https://adsense.google.com).
2. Di menu samping kiri, klik **Iklan** *(Ads)* → tab **Menurut unit iklan** *(By ad unit)*.
3. Pilih opsi **Iklan Display** *(Display ads)*.
4. Isi opsi berikut:
   - **Nama unit iklan**: Misal `Portal News Sidebar`
   - **Ukuran iklan**: Pilih **Responsif** *(Responsive)*
5. Klik **Buat** *(Create)*.
6. Kode HTML AdSense akan muncul seperti ini:
   ```html
   <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1234567890123456"
        crossorigin="anonymous"></script>
   <!-- Portal News Sidebar -->
   <ins class="adsbygoogle"
        style="display:block"
        data-ad-client="ca-pub-1234567890123456"
        data-ad-slot="1234567890"
        data-ad-format="auto"
        data-full-width-responsive="true"></ins>
   <script>
        (adsbygoogle = window.adsbygoogle || []).push({});
   </script>
   ```
7. Salin angka pada atribut `data-ad-slot="..."` (pada contoh di atas: `1234567890`).
8. Tempelkan angka tersebut ke `ADSENSE_SIDEBAR_SLOT` di file `.env`.

---

### 3. `ADSENSE_IN_ARTICLE_SLOT` (Unit Iklan Dalam Artikel)

**Lokasi Menu AdSense:**
1. Login ke [Dashboard Google AdSense](https://adsense.google.com).
2. Di menu samping kiri, klik **Iklan** *(Ads)* → tab **Menurut unit iklan** *(By ad unit)*.
3. Pilih opsi **Iklan Dalam Artikel** *(In-article ads)*.
4. Isi opsi berikut:
   - **Nama unit iklan**: Misal `Portal News In-Article`
5. Klik **Simpan dan Dapatkan Kode** *(Save and get code)*.
6. Kode HTML AdSense akan muncul seperti ini:
   ```html
   <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1234567890123456"
        crossorigin="anonymous"></script>
   <ins class="adsbygoogle"
        style="display:block; text-align:center;"
        data-ad-layout="in-article"
        data-ad-format="fluid"
        data-ad-client="ca-pub-1234567890123456"
        data-ad-slot="0987654321"></ins>
   <script>
        (adsbygoogle = window.adsbygoogle || []).push({});
   </script>
   ```
7. Salin angka pada atribut `data-ad-slot="..."` (pada contoh di atas: `0987654321`).
8. Tempelkan angka tersebut ke `ADSENSE_IN_ARTICLE_SLOT` di file `.env`.

---

## 📄 4. Menyiapkan File `ads.txt`

Google AdSense mewajibkan file `ads.txt` berada di root domain utama (misal: `https://domain-anda.com/ads.txt`).

### Dimana Lokasi File `ads.txt` di Project ini?
Lokasi file ada di dalam folder `public/`:
📁 `d:\amri\myweb\blogger\news-portal\public\ads.txt`

*(Di cPanel hosting, file ini diupload ke dalam folder `public_html/ads.txt`)*.

### Format Isi File `ads.txt`:
Buka file `public/ads.txt` dan isi dengan 1 baris berikut:

```text
google.com, pub-XXXXXXXXXXXXXXXX, DIRECT, f08c47fec0942fa0
```

> ⚠️ **Catatan penting:**
> Ganti `pub-XXXXXXXXXXXXXXXX` dengan angka Publisher ID Anda (tanpa imbuhan `ca-`).
> 
> Contoh: Jika `ADSENSE_CLIENT_ID` Anda di `.env` adalah `ca-pub-1234567890123456`, maka isi `ads.txt` adalah:
> ```text
> google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0
> ```

---

## ⚡ Langkah Terakhir Setelah Mengisi `.env` dan `ads.txt`

Setelah selesai mengedit file `.env` dan `public/ads.txt`, jalankan perintah berikut di terminal:

```bash
php artisan config:clear
```

Google AdSense akan otomatis memverifikasi situs Anda saat melakukan crawl berikutnya! 🎉

