# PROMPT: GENERATOR ISI BERITA UNTUK IMPORT BEBASINFO

Gunakan prompt ini untuk membuat isi berita otomatis dalam format `posts.json` yang siap dipakai oleh fitur Import Berita BebasInfo.

Tugas utama:

Buat kumpulan berita berkualitas untuk website BebasInfo dalam format JSON import. Output harus siap disimpan sebagai `posts.json`, lalu dimasukkan ke ZIP bersama folder `images/`.

## Input dari Pengguna

Gunakan data berikut sebagai sumber:

```text
JUMLAH_BERITA: [isi jumlah berita]
TOPIK_UTAMA: [isi topik utama]
KATEGORI: [Nasional/Ekonomi/Teknologi c/Olahraga/Gaya Hidup/dll]
LOKASI: [opsional]
TANGGAL_PUBLIKASI: [YYYY-MM-DD HH:mm:ss atau kosong]
STATUS: draft
GAYA_BAHASA: profesional, jelas, netral, mudah dipahami
TARGET_PEMBACA: pembaca umum Indonesia
FAKTA_SUMBER: [masukkan poin fakta, data, kutipan, atau ringkasan sumber]
```

## Aturan Penting

- Jangan mengarang fakta, angka, kutipan, nama narasumber, lokasi, atau kejadian nyata jika tidak ada di `FAKTA_SUMBER`.
- Jika data sumber minim, buat artikel evergreen, edukatif, opini umum, atau analisis ringan tanpa mengklaim peristiwa faktual spesifik.
- Jangan membuat berita hoaks, fitnah, tuduhan kriminal, data medis/legal/finansial spesifik tanpa sumber.
- Gunakan bahasa Indonesia yang natural.
- Brand website hanya `BebasInfo`.
- Jangan menulis atribut `alt` kosong.
- Semua konten harus aman untuk publikasi.

## Format Output

Output hanya JSON valid. Jangan tambahkan penjelasan di luar JSON.

Struktur:

```json
{
  "posts": [
    {
      "title": "",
      "slug": "",
      "excerpt": "",
      "content": "",
      "category": "",
      "tags": [],
      "status": "draft",
      "published_at": null,
      "is_featured": false,
      "featured_image": "images/nama-gambar.jpg",
      "featured_image_alt": "",
      "featured_image_caption": "",
      "featured_image_credit": "BebasInfo",
      "detail_images": [],
      "seo_title": "",
      "seo_description": "",
      "robots_index": true,
      "robots_follow": true,
      "image_prompt": ""
    }
  ]
}
```

## Aturan Field

`title`

- Judul berita jelas, ringkas, dan tidak clickbait.
- Maksimal sekitar 80 karakter.

`slug`

- Gunakan huruf kecil.
- Gunakan tanda hubung.
- Tanpa karakter khusus.
- Harus unik.

`excerpt`

- Ringkasan 1 sampai 2 kalimat.
- Maksimal sekitar 160 sampai 220 karakter.

`content`

- Gunakan HTML sederhana.
- Minimal 5 paragraf.
- Gunakan struktur:
  - paragraf pembuka
  - konteks utama
  - detail pendukung
  - dampak atau manfaat bagi pembaca
  - penutup
- Boleh gunakan `<h2>` untuk subjudul.
- Boleh gunakan `<ul><li>` jika relevan.
- Jangan gunakan `<script>`, iframe, style inline, atau event handler.
- Jika artikel membutuhkan gambar di dalam konten, gunakan:

```html
<p><img src="images/nama-gambar-konten.jpg" alt="Deskripsi gambar"></p>
```

`category`

- Pilih kategori paling relevan.
- Contoh: Nasional, Ekonomi, Teknologi, Olahraga, Gaya Hidup, Pendidikan, Kesehatan, Internasional.

`tags`

- Isi 3 sampai 6 tag relevan.
- Tag singkat dan natural.

`status`

- Gunakan `draft` kecuali diminta lain.

`published_at`

- Gunakan `null` jika status `draft`.
- Jika status `published`, isi format:

```text
YYYY-MM-DD HH:mm:ss
```

`featured_image`

- Gunakan path relatif:

```text
images/[slug].jpg
```

- Jika belum ada gambar, tetap isi path rekomendasi agar tim bisa menyiapkan file gambar di ZIP.

`featured_image_alt`

- Deskripsikan isi gambar.
- Jika gambar bersifat ilustrasi, sebutkan sebagai ilustrasi.
- Jangan kosong.

`featured_image_caption`

- Keterangan gambar yang natural.
- Jangan membuat klaim lokasi atau orang spesifik jika tidak ada sumber.

`featured_image_credit`

- Gunakan `BebasInfo`.

`detail_images`

- Opsional.
- Gunakan array path relatif, contoh `["images/detail-1.jpg", "images/detail-2.jpg"]`.
- Jika kosong, halaman detail memakai `featured_image`.

`seo_title`

- Maksimal sekitar 60 karakter.
- Boleh sama dengan judul jika sudah bagus.

`seo_description`

- Sekitar 150 sampai 160 karakter.
- Harus merangkum isi berita dengan jelas.

`image_prompt`

- Buat prompt gambar realistis untuk featured image.
- Harus relevan dengan isi berita.
- Jangan memakai logo, teks, watermark, wajah tokoh nyata, atau klaim lokasi spesifik jika tidak ada sumber.
- Rasio gambar 16:9.
- Gaya foto jurnalistik bersih dan natural.

## Standar Gambar untuk ZIP

Saat membuat file gambar berdasarkan `image_prompt`, simpan ke path yang sama dengan `featured_image`.

Aturan gambar:

- JPG, JPEG, PNG, atau WebP.
- Maksimal 5 MB.
- Minimal 1200 x 675 piksel.
- Rasio disarankan 16:9.
- Gunakan gambar berkualitas tinggi yang relevan dengan berita.

## Contoh Perintah

Buat 5 berita untuk BebasInfo.

Topik utama:

```text
Perkembangan layanan publik digital di Indonesia.
```

Kategori:

```text
Teknologi
```

Fakta sumber:

```text
- Banyak layanan administrasi kini tersedia melalui aplikasi dan portal web.
- Pembaca membutuhkan panduan memahami manfaat, risiko keamanan data, dan cara memakai layanan digital dengan aman.
- Tidak ada data angka resmi yang diberikan.
```

Status:

```text
draft
```

Output harus berupa JSON valid untuk `posts.json`.

## Contoh Output Singkat

```json
{
  "posts": [
    {
      "title": "Layanan Publik Digital Makin Dekat dengan Kebutuhan Warga",
      "slug": "layanan-publik-digital-makin-dekat-dengan-kebutuhan-warga",
      "excerpt": "Layanan publik digital membantu warga mengakses administrasi lebih mudah, tetapi keamanan data tetap perlu menjadi perhatian.",
      "content": "<p>Layanan publik digital kini menjadi bagian penting dalam aktivitas masyarakat...</p><h2>Manfaat bagi warga</h2><p>Melalui aplikasi dan portal web, warga dapat mengakses informasi dan layanan secara lebih praktis.</p><h2>Keamanan tetap penting</h2><p>Pengguna perlu memastikan situs yang dibuka resmi dan tidak membagikan kode rahasia kepada pihak lain.</p><p>Dengan literasi digital yang baik, layanan publik digital dapat memberi manfaat lebih luas bagi masyarakat.</p>",
      "category": "Teknologi",
      "tags": ["Layanan Publik", "Digital", "Keamanan Data", "Teknologi"],
      "status": "draft",
      "published_at": null,
      "is_featured": false,
      "featured_image": "images/layanan-publik-digital-makin-dekat-dengan-kebutuhan-warga.jpg",
      "featured_image_alt": "Ilustrasi warga menggunakan layanan publik digital melalui laptop",
      "featured_image_caption": "Ilustrasi pemanfaatan layanan publik digital oleh masyarakat.",
      "featured_image_credit": "BebasInfo",
      "detail_images": [],
      "seo_title": "Layanan Publik Digital dan Manfaatnya bagi Warga",
      "seo_description": "Layanan publik digital memudahkan akses administrasi warga, tetapi keamanan data dan literasi digital tetap perlu diperhatikan.",
      "robots_index": true,
      "robots_follow": true,
      "image_prompt": "Foto jurnalistik realistis 16:9, warga Indonesia menggunakan laptop dan ponsel untuk mengakses layanan publik digital, suasana modern bersih, tanpa teks, tanpa logo, tanpa watermark"
    }
  ]
}
```
