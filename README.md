# BebasInfo

Fondasi backend portal berita menggunakan Laravel, MySQL, dan Filament.

Tahap yang sudah selesai:

- Tahap 1 sesuai `be.md`: project Laravel, Filament panel, konfigurasi dasar, koneksi MySQL, admin development, proteksi akses panel, storage, build asset, migration bawaan, dan testing dasar.
- Tahap 2 sesuai `be2.md`: enum, database portal berita, model, relasi, factory, seeder development, scope query dasar, dan testing model.
- Tahap 3 sesuai `be3.md`: akses panel berdasarkan role aktif, helper role user, status transition, policy model utama, dan testing authorization.
- Tahap 4 sesuai `tahap4.md`: Filament Resource untuk Editor, Kategori, dan Tag beserta form, table, filter, action, authorization, dan testing.
- Tahap 5 sesuai `tahap5.md`: Filament Post Resource, form berita, tabel berita, workflow status, pembatasan query Editor, soft delete Admin, dan testing workflow.
- Tahap 6 sesuai `tahap6.md`: upload featured image, optimasi WebP, variasi ukuran gambar, rich text editor, sanitasi konten, upload gambar konten editor, observer cleanup, dan testing media.
- Tahap 7 sesuai `tahap7.md`: redirect slug otomatis, Redirect Resource, Site Settings Page, typed settings cache, Content URL service, SEO data/service, preview SEO, dan testing redirect/settings/SEO.
- Tahap 8 sesuai `tahap8.md`: frontend publik Laravel Blade, homepage, daftar/detail berita, kategori, tag, metadata SEO dasar, redirect HTTP slug lama, responsive image, breadcrumb, 404, aksesibilitas dasar, dan testing frontend publik.
- Tahap 9 sesuai `tahap9.md`: search publik, Schema.org JSON-LD, sitemap XML, RSS feed, robots.txt dinamis, cache publik, invalidasi cache konten, security headers frontend, dan testing SEO teknis.
- Tahap 10 sesuai `tahap10.md`: audit final, command aman membuat admin production, template environment production, dokumentasi deployment shared hosting cPanel, rollback, operasi, checklist release, security/dependency audit, dan verifikasi build final.

Belum ada Google Analytics, newsletter, komentar, statistik pembaca, queue, scheduler otomatis publikasi, CDN, object storage eksternal, media library kompleks, atau deployment production.

## Teknologi

- PHP 8.3
- Laravel 13
- Filament 5
- MySQL 8
- Vite
- Laravel Pint
- PHPUnit
- Intervention Image 4

## Kebutuhan Lokal

Pastikan tools berikut tersedia:

```bash
php -v
composer --version
node -v
npm.cmd -v
php -m
```

Ekstensi PHP penting yang perlu aktif:

- `bcmath`
- `ctype`
- `curl`
- `dom`
- `fileinfo`
- `filter`
- `gd`
- `exif`
- `intl`
- `mbstring`
- `openssl`
- `pdo_mysql`
- `session`
- `tokenizer`
- `xml`
- `zip`

## Setup Project

Masuk ke folder project:

```bash
cd news-portal
```

Install dependency PHP:

```bash
composer install
```

Install dependency Node:

```bash
npm.cmd install
```

Salin environment jika `.env` belum ada:

```bash
copy .env.example .env
```

Buat application key:

```bash
php artisan key:generate
```

Build asset:

```bash
npm.cmd run build
```

## Konfigurasi Database

Contoh konfigurasi lokal di `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=news_portal
DB_USERNAME=root
DB_PASSWORD=
```

Buat database MySQL:

```sql
CREATE DATABASE news_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Jalankan migration bawaan:

```bash
php artisan migrate
```

Jalankan seeder admin development:

```bash
php artisan db:seed
```

## Admin Development

Credential ini hanya untuk development lokal.

```text
URL dashboard: http://127.0.0.1:8000/admin
Email: admin@example.test
Password: change-this-password
```

Nilai credential diambil dari `.env`:

```env
ADMIN_NAME="Development Admin"
ADMIN_EMAIL=admin@example.test
ADMIN_PASSWORD=change-this-password
```

Jangan gunakan password ini di production. Untuk production, ganti nilai `ADMIN_EMAIL` dan `ADMIN_PASSWORD` di environment server sebelum menjalankan seeder, atau buat user admin dengan mekanisme yang lebih aman.

Admin login dapat dimatikan sementara dengan env:

```env
ADMIN_LOGIN_ENABLED=false
```

Jika bernilai `false`, halaman `/admin/login` dan seluruh panel `/admin` akan redirect ke homepage.

## Admin Production

Jangan menjalankan seeder development untuk membuat admin production. Gunakan command:

```bash
php artisan admin:create
```

Command akan meminta nama, email, password, dan konfirmasi. Password tidak dicetak ke output dan disimpan melalui cast hash model `User`.

Gunakan `.env.production.example` sebagai template environment production. Isi credential hanya di server, jangan commit ke repository.

## Cara Menjalankan

Jalankan Laravel development server:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Buka:

```text
Homepage: http://127.0.0.1:8000
Dashboard: http://127.0.0.1:8000/admin
Login: http://127.0.0.1:8000/admin/login
```

Jika sedang mengubah asset frontend, jalankan Vite development server di terminal lain:

```bash
npm.cmd run dev
```

## Storage

Buat symbolic link storage:

```bash
php artisan storage:link
```

Konfigurasi awal menggunakan:

```env
FILESYSTEM_DISK=public
```

Di shared hosting cPanel, symbolic link kadang dibatasi provider. Jika gagal, ikuti aturan provider hosting dan jangan menyimpan upload langsung sembarangan ke root `public`.

Jika `php artisan storage:link` menampilkan pesan link sudah ada, tidak perlu menjalankannya ulang.

## Tahap 2: Database Portal Berita

Tahap 2 menambahkan struktur database dan model untuk portal berita.

Entity utama:

```text
User
  hasMany Post sebagai author
  hasMany Post sebagai editor

Category
  hasMany Post

Post
  belongsTo User sebagai author
  belongsTo User sebagai editor
  belongsTo Category
  belongsToMany Tag
  hasMany PostRedirect, dipertahankan dengan `post_id = null` jika Post force delete

Tag
  belongsToMany Post

PostRedirect
  belongsTo Post secara nullable

SiteSetting
  menyimpan pengaturan website sederhana
```

Enum yang tersedia:

```text
App\Enums\UserRole: admin, editor
App\Enums\PostStatus: draft, review, scheduled, published, archived
```

Seeder development membuat:

- 1 Admin.
- 3 Editor.
- 5 kategori.
- 15 tag.
- 30 berita dengan berbagai status.
- 5 berita featured.
- 5 redirect slug contoh.
- 9 site setting dasar.

Untuk reset database development dan mengisi ulang data contoh:

```bash
php artisan migrate:fresh --seed
```

Perintah ini hanya aman untuk development karena menghapus seluruh tabel.

## Tahap 3: Authorization

Tahap 3 menambahkan fondasi authorization untuk dashboard dan model.

Aturan akses panel Filament:

```text
user.is_active = true
role = admin atau editor
```

Role dan status harus menggunakan enum:

```text
App\Enums\UserRole
App\Enums\PostStatus
```

Policy yang tersedia:

- `PostPolicy`
- `CategoryPolicy`
- `TagPolicy`
- `UserPolicy`
- `PostRedirectPolicy`
- `SiteSettingPolicy`

Aturan kepemilikan berita Editor:

```text
posts.author_id === users.id
```

Editor hanya boleh melihat dan mengedit berita miliknya sendiri. Untuk daftar berita di Filament Resource tahap berikutnya, query Editor harus dibatasi dengan `author_id = auth()->id()`. Jangan memakai global scope berbasis user login karena bisa mengganggu scheduler, seeder, command, admin, dan query internal.

Aturan transisi status berada di:

```text
app/Support/PostStatusTransitions.php
```

Admin tetap harus mengikuti transisi yang valid. Admin tidak langsung publish dari `draft`; alur yang disarankan adalah `draft -> review -> published` atau `draft -> scheduled -> published`. Editor hanya boleh:

```text
draft -> review
review -> draft
```

Policy sudah didaftarkan eksplisit di `AppServiceProvider`, sehingga Filament Resource tahap berikutnya dapat memakai authorization Laravel untuk `viewAny`, `create`, `update`, `delete`, `restore`, dan `forceDelete`.

## Tahap 4: Filament Resource

Resource yang tersedia:

```text
Berita: /admin/posts
Editor: /admin/users
Kategori: /admin/categories
Tag: /admin/tags
```

Navigasi dashboard:

```text
Manajemen Konten
  Berita
  Kategori
  Tag

Manajemen Pengguna
  Editor
```

Aturan Resource Editor:

- Hanya Admin aktif yang dapat membuka menu Editor.
- Daftar Editor hanya mengambil user dengan role `editor`.
- Akun Admin tidak tampil dan tidak dapat diedit melalui URL Resource Editor.
- Role tidak tampil di form dan selalu dipaksa menjadi `UserRole::Editor`.
- Password wajib saat create.
- Password opsional saat edit; jika kosong, password lama tidak berubah.
- Password disimpan melalui cast `password => hashed` di model `User`, sehingga tidak perlu `Hash::make()` di form.
- Delete user tidak disediakan; gunakan aktif/nonaktif.

Aturan Resource Kategori:

- Admin dapat membuat, mengedit, dan menghapus kategori.
- Editor hanya dapat melihat daftar kategori.
- Slug dibuat dari nama saat create dan tetap dapat diedit manual.
- Tabel menggunakan `withCount('posts')` untuk jumlah berita.
- Kategori yang masih dipakai post tidak dapat dihapus dan akan menampilkan notifikasi ramah.

Aturan Resource Tag:

- Admin dapat membuat, mengedit, dan menghapus tag.
- Editor hanya dapat melihat daftar tag.
- Slug dibuat dari nama saat create dan tetap dapat diedit manual.
- Menghapus tag tidak menghapus post; hanya pivot `post_tag` yang ikut terhapus.

## Tahap 5: Post Resource dan Workflow

Resource Berita tersedia di:

```text
Dashboard: http://127.0.0.1:8000/admin/posts
Create: http://127.0.0.1:8000/admin/posts/create
```

### Import Berita

Admin dapat mengimpor berita dari halaman `/admin/posts` melalui tombol **Import Berita**.
Gunakan tombol **Download Template** untuk mengunduh ZIP contoh yang sudah berisi `posts.json` dan contoh gambar.

Format yang disarankan agar gambar ikut terimport adalah ZIP:

```text
import-berita.zip
├── posts.json
└── images/
    ├── berita-utama.jpg
    └── konten-1.webp
```

Contoh `posts.json`:

```json
{
  "posts": [
    {
      "title": "Judul Berita Import",
      "slug": "judul-berita-import",
      "excerpt": "Ringkasan singkat berita.",
      "content": "<p>Isi berita.</p><p><img src=\"images/konten-1.webp\" alt=\"Foto pendukung\"></p>",
      "category": "Nasional",
      "tags": ["Politik", "Pemerintahan"],
      "status": "draft",
      "featured_image": "images/berita-utama.jpg",
      "featured_image_alt": "Ilustrasi berita utama",
      "featured_image_caption": "Keterangan foto",
      "featured_image_credit": "BebasInfo",
      "seo_title": "Judul SEO berita",
      "seo_description": "Deskripsi SEO berita sekitar 160 karakter."
    }
  ]
}
```

Catatan import:

- `posts.json` wajib berada di root ZIP.
- `featured_image` dan gambar di dalam `content` memakai path relatif di dalam ZIP.
- Jika `featured_image` kosong, frontend otomatis memakai default image.
- Gambar utama tetap divalidasi: JPG, JPEG, PNG, atau WebP; maksimal 5 MB; resolusi minimal 1200 x 675 piksel.
- Kategori dan tag akan dibuat otomatis jika belum ada.
- Slug duplikat akan diberi suffix otomatis, misalnya `judul-berita-2`.

### BebasInfo News Import API

Generator Python lokal dapat mengirim hasil berita ke Laravel melalui endpoint API:

```http
POST /api/import/news
Authorization: Bearer {IMPORT_TOKEN}
Accept: application/json
Content-Type: multipart/form-data
```

Field multipart:

```text
package=bebasinfo-import.zip
publish_mode=draft|published
```

Struktur ZIP API:

```text
bebasinfo-import.zip
+-- manifest.json
+-- posts.json
+-- sources.json
+-- images/
    +-- featured.jpg
    +-- content.webp
```

Token import dibuat Admin lewat menu dashboard `Import Berita > Import Tokens`. Token hanya tampil satu kali setelah dibuat, disimpan sebagai hash, dapat diberi expiry, dapat dicabut, dan memiliki abilities:

```text
news:import
news:publish
```

Aturan status API:

- `publish_mode=draft` hanya membutuhkan `news:import`, menyimpan `status=draft`, dan `published_at=null`.
- `publish_mode=published` membutuhkan `news:import` dan `news:publish`.
- Laravel selalu menentukan `published_at=now()` untuk publish dan mengabaikan `published_at`, `author_id`, `user_id`, serta status dari `posts.json`.
- Header `Idempotency-Key` dapat dikirim agar retry dari Python tidak membuat post duplikat.

Keamanan import API:

- Endpoint memakai Bearer Import Token khusus, bukan password admin, session cookie, atau CSRF token.
- ZIP disimpan dan diekstrak di private storage sementara, bukan public storage.
- ZIP slip, path traversal, absolute path, nested ZIP, file PHP, `.env`, script, executable, dan file selain `manifest.json`, `posts.json`, `sources.json`, atau `images/*.{jpg,jpeg,png,webp}` ditolak.
- Batas ukuran, jumlah artikel, jumlah file, total ekstraksi, publish permission, fallback image, rate limit, dan retention diatur di `config/news-import.php`.
- Sumber artikel dari `sources.json` disimpan sebagai audit metadata, tanpa raw HTML sumber.
- Satu artikel gagal tidak menggagalkan seluruh package; item gagal dicatat di `News Imports`.

Environment API import:

```env
NEWS_IMPORT_ENABLED=true
NEWS_IMPORT_ASYNC=false
NEWS_IMPORT_MAX_ZIP_MB=50
NEWS_IMPORT_MAX_POSTS=20
NEWS_IMPORT_MAX_FILES=100
NEWS_IMPORT_MAX_UNCOMPRESSED_MB=200
NEWS_IMPORT_ALLOW_PUBLISH=true
NEWS_IMPORT_ALLOW_DEFAULT_IMAGE=true
NEWS_IMPORT_DEFAULT_IMAGE_PATH=
NEWS_IMPORT_TOKEN_EXPIRY_DAYS=90
NEWS_IMPORT_RATE_LIMIT=10
NEWS_IMPORT_LOG_RETENTION_DAYS=90
NEWS_IMPORT_STRICT_MODE=false
```

Endpoint status import:

```http
GET /api/import/news/{uuid}
Authorization: Bearer {IMPORT_TOKEN}
Accept: application/json
```

Maintenance import:

```bash
php artisan news-imports:cleanup
```

Scheduler menjalankan cleanup harian pukul `02:30`. Di cPanel, pasang cron untuk menjalankan Laravel scheduler, misalnya:

```bash
* * * * * cd /home/USER/news-portal && php artisan schedule:run >> /dev/null 2>&1
```

Untuk production, pastikan `storage` writable, `php artisan migrate --force` sudah dijalankan, `php artisan storage:link` tersedia atau diganti mekanisme symlink provider, endpoint memakai HTTPS, dan batas PHP seperti `upload_max_filesize`, `post_max_size`, `memory_limit`, serta `max_execution_time` cukup untuk ZIP import.

Aturan Resource Berita:

- Admin dapat melihat seluruh berita, membuat draft, mengedit seluruh berita, mengatur penulis, memilih peninjau, mengatur featured dan robots, menjalankan workflow lengkap, soft delete, melihat trashed, restore, dan force delete sesuai policy.
- Editor hanya melihat berita dengan `author_id` miliknya sendiri.
- Editor dapat membuat draft dan mengedit berita miliknya selama status masih `draft` atau `review`.
- Editor tidak dapat mengubah `author_id`, `editor_id`, `status`, `published_at`, `is_featured`, `robots_index`, atau `robots_follow` lewat manipulasi form.
- Status berita tidak diubah lewat select bebas. Semua perubahan status melewati `App\Actions\Post\TransitionPostStatusAction`.
- Workflow yang tersedia: kirim untuk review, kembalikan ke draf, jadwalkan, terbitkan sekarang, arsipkan, batalkan jadwal, dan aktifkan kembali sebagai draf.
- Schedule menggunakan timezone aplikasi `Asia/Jakarta` dan menolak waktu publikasi yang sudah lewat.
- Publish mengisi `published_at` dengan waktu aktual, sedangkan archive mempertahankan riwayat `published_at`.
- Relasi kategori memakai kategori aktif untuk pilihan berita baru, dan relasi tag disimpan melalui field multi-select.
- Field gambar pada tahap ini sudah dilanjutkan menjadi upload media pada tahap 6.
- Konten berita disimpan pada kolom `content` bertipe long text. Saat frontend publik dibuat nanti, output konten tetap harus disanitasi sebelum ditampilkan.

File workflow utama:

```text
app/Actions/Post/TransitionPostStatusAction.php
app/Exceptions/InvalidPostStatusTransitionException.php
app/Filament/Resources/Posts/PostResource.php
```

## Tahap 6: Media dan Rich Text Editor

Tahap 6 menambahkan upload gambar dan editor konten berita.

Dependency media:

```text
intervention/image: 4.2
Driver image: GD
Disk storage: public
```

Konfigurasi media berada di:

```text
config/media.php
```

Struktur folder storage:

```text
storage/app/public/posts/featured/YYYY/MM
storage/app/public/posts/content/YYYY/MM
```

Path yang disimpan di database tetap relatif, contohnya:

```text
posts/featured/2026/07/uuid.webp
```

URL publik dibangun melalui:

```php
Storage::disk('public')->url($path)
```

Aturan featured image:

- Format input yang diterima: JPG, JPEG, PNG, WEBP.
- SVG, GIF, file non-gambar, MIME palsu, dan gambar korup ditolak.
- Ukuran maksimal upload aplikasi: 5 MB. Nilai `max` Laravel/Filament memakai satuan kilobyte.
- Resolusi minimal featured image: 1200 x 675 piksel.
- Rasio yang disarankan: 16:9.
- Gambar yang lebih besar dari ukuran output tidak ditolak selama memenuhi batas 5 MB dan resolusi minimum; file akan dioptimalkan dan di-resize sesuai konfigurasi.
- Output utama menggunakan WebP jika GD mendukung WebP.
- Nama file memakai UUID, bukan nama file asli.
- Path utama disimpan di kolom `featured_image`.
- Variasi dibuat dengan pola `-large`, `-medium`, dan `-thumbnail`.
- Alt text wajib jika `featured_image` tersedia.
- Caption dan credit opsional.
- Soft delete post tidak menghapus file gambar.
- Force delete post membersihkan featured image dan variasinya melalui `PostObserver`.
- Saat replace, file lama baru dihapus setelah database berhasil di-update.

Ukuran featured image:

```text
Original/Large: 1600 x 900
Medium: 960 x 540
Thumbnail: 480 x 270
```

Default featured image:

- Jika Post tidak memiliki `featured_image`, semua helper URL gambar memakai default image.
- Default config berada di `config/media.php` pada key `media.featured.default_image`.
- Default alt text berada di `config/media.php` pada key `media.featured.default_alt`.
- Nilai production dapat diubah melalui `.env`:

```env
MEDIA_DEFAULT_FEATURED_IMAGE=/images/default.png
MEDIA_DEFAULT_FEATURED_ALT="Gambar berita"
```

- File default bawaan berada di `public/images/default.png`.
- Komponen frontend tidak perlu melakukan pengecekan gambar kosong secara manual.
- Helper gambar selalu mengembalikan URL absolut.
- Jika alt text kosong, frontend memakai judul Post; jika judul kosong, memakai default alt config.
- Frontend tidak menghasilkan atribut `alt=""`.
- Featured image dipakai untuk homepage, category, tag, search result, Open Graph, Twitter Card, dan JSON-LD `NewsArticle`.

Rich text editor:

- Field `content` memakai RichEditor bawaan Filament.
- Toolbar dibatasi ke heading, bold, italic, underline, link, list, blockquote, image attachment, undo, dan redo.
- Konten disanitasi sebelum disimpan menggunakan `App\Services\ContentSanitizer`.
- Konten kosong seperti `<p><br></p>` ditolak oleh `App\Rules\MeaningfulRichText`.
- Script, event handler, iframe, form element, dan URL `javascript:` dibersihkan.
- Link dengan `target="_blank"` diberi `rel="noopener noreferrer"`.
- Gambar konten editor disimpan ke `posts/content/YYYY/MM`, diproses WebP, dan tidak disimpan sebagai base64.
- Cleanup otomatis gambar konten belum dilakukan karena belum ada tracking kepemilikan file per artikel. Cleanup administratif bisa dibuat di tahap terpisah dengan mode `--dry-run`.

File utama tahap 6:

```text
app/Services/PostImageService.php
app/Services/ContentSanitizer.php
app/Rules/MeaningfulRichText.php
app/Observers/PostObserver.php
config/media.php
```

Rekomendasi PHP upload untuk cPanel:

```ini
upload_max_filesize = 8M
post_max_size = 10M
memory_limit = 256M
max_execution_time = 120
```

Di cPanel, pengaturan ini biasanya tersedia melalui MultiPHP INI Editor, Select PHP Version, atau `.user.ini` jika provider mendukung. `post_max_size` harus lebih besar dari `upload_max_filesize`, dan `upload_max_filesize` harus lebih besar dari batas aplikasi 5 MB.

## Tahap 7: Redirect, Site Settings, dan SEO Backend

Tahap 7 menambahkan backend SEO tanpa membuat frontend publik.

Resource dan page baru:

```text
Redirect: /admin/post-redirects
Pengaturan Situs: /admin/site-settings
```

Konfigurasi content URL berada di:

```text
config/content.php
```

Default path:

```text
Post: /berita/{slug}
Kategori: /kategori/{slug}
```

Redirect slug Post:

- Perubahan slug Post membuat redirect `301` dari path lama ke path baru.
- Slug yang tidak berubah tidak membuat redirect.
- Redirect dibuat di dalam transaksi update Post melalui `App\Services\PostSlugRedirectService`.
- Redirect otomatis hanya untuk Post. Perubahan slug Category belum membuat redirect karena route kategori publik belum final.
- Redirect chain dinormalisasi agar URL lama langsung menuju slug terbaru.
- Redirect loop langsung maupun tidak langsung ditolak.
- Path redirect hanya boleh internal, diawali `/`, tanpa domain, query, atau fragment.
- Duplicate slash dan trailing slash dinormalisasi.
- Soft delete Post tidak menghapus redirect.
- Force delete Post mempertahankan redirect dan mengubah `post_id` menjadi `null`.
- Resolver redirect tersedia untuk frontend tahap berikutnya, tetapi belum menghasilkan response HTTP.

File redirect utama:

```text
app/Support/RedirectPathNormalizer.php
app/Services/ContentUrlService.php
app/Services/RedirectService.php
app/Services/PostSlugRedirectService.php
app/Services/RedirectResolver.php
app/Filament/Resources/PostRedirects/PostRedirectResource.php
```

Site Settings:

- Menggunakan model key-value `SiteSetting`.
- Admin aktif dapat membuka dan menyimpan pengaturan.
- Editor tidak dapat melihat menu, membuka URL, atau menyimpan perubahan.
- Setting sensitif seperti password, secret, token, credential, dan private key tidak disimpan di Site Settings.
- Cache settings memakai key `site_settings.all` dan `Cache::rememberForever()`, kompatibel dengan file cache/shared hosting.
- Cache dibersihkan setelah update.
- Logo, favicon, dan default Open Graph image disimpan di disk `public`.
- File branding lama dihapus setelah update database berhasil.

Config Site Settings:

```text
config/site-settings.php
```

Setting minimal:

```text
site_name
site_tagline
site_description
site_logo
site_favicon
contact_email
contact_phone
contact_address
default_seo_title
default_seo_description
default_og_image
default_robots_index
default_robots_follow
facebook_url
instagram_url
youtube_url
x_url
tiktok_url
footer_text
```

File settings utama:

```text
app/Services/SiteSettingService.php
app/Filament/Pages/ManageSiteSettings.php
```

SEO backend:

- `App\Data\SeoData` menyimpan metadata terstruktur.
- `App\Services\SeoService` membuat metadata untuk Post, Category, dan Home.
- Service SEO tidak merender HTML dan tidak mengakses user login.
- Metadata menggunakan fallback dari field model, Site Settings, dan URL builder.
- Canonical custom harus URL absolut dengan scheme HTTP atau HTTPS.
- Post non-published menghasilkan `noindex, nofollow`.
- Post archived menghasilkan `noindex, follow`.
- Post published mengikuti field robots di Post.
- Category nonaktif menghasilkan `noindex, nofollow`.
- OG image menggunakan fallback `post.og_image`, `post.featured_image`, `default_og_image`, lalu `site_logo`.
- Preview SEO sederhana tersedia di form Post dan Category.

File SEO utama:

```text
app/Data/SeoData.php
app/Services/SeoService.php
```

## Tahap 8: Frontend Publik Blade

Tahap 8 menambahkan frontend publik menggunakan Laravel Blade, Tailwind CSS, dan Vite.

Route publik:

```text
Home: /
Pencarian: /cari?q=kata-kunci
Daftar berita: /berita
Detail berita: /berita/{slug}
Kategori: /kategori/{slug}
Tag: /tag/{slug}
```

Nama route:

```text
home
search
posts.index
posts.show
categories.show
tags.show
```

Aturan publikasi frontend:

- Hanya Post dengan status `published`, `published_at` tidak null, `published_at <= now()`, dan tidak soft deleted yang tampil publik.
- Draft, review, scheduled, archived, soft deleted, dan published dengan tanggal masa depan menghasilkan 404 atau tidak muncul di daftar.
- Detail Post memakai query slug manual, bukan route model binding, agar Post non-published tidak pernah terbuka publik.
- Jika slug tidak ditemukan, controller memeriksa `RedirectResolver` dan menjalankan redirect internal 301/302 sesuai data redirect aktif.
- Redirect runtime dilakukan satu kali; chain tetap dinormalisasi di backend tahap 7.

View publik utama:

```text
resources/views/layouts/public.blade.php
resources/views/home.blade.php
resources/views/posts/index.blade.php
resources/views/posts/show.blade.php
resources/views/categories/show.blade.php
resources/views/tags/show.blade.php
resources/views/errors/404.blade.php
```

Komponen publik:

```text
resources/views/components/public/header.blade.php
resources/views/components/public/navigation.blade.php
resources/views/components/public/mobile-navigation.blade.php
resources/views/components/public/footer.blade.php
resources/views/components/public/post-card.blade.php
resources/views/components/public/featured-post-card.blade.php
resources/views/components/public/responsive-image.blade.php
resources/views/components/public/breadcrumb.blade.php
resources/views/components/public/empty-state.blade.php
resources/views/components/public/section-heading.blade.php
```

Service/provider frontend:

```text
app/Http/Controllers/HomeController.php
app/Http/Controllers/PostController.php
app/Http/Controllers/CategoryController.php
app/Http/Controllers/TagController.php
app/Providers/ViewServiceProvider.php
app/Services/PostImageUrlService.php
```

SEO frontend:

- Layout publik merender `<title>`, description, canonical, robots, Open Graph, dan Twitter Card dari `SeoData`.
- Controller mengirim `SeoData`; Blade tidak memanggil `SeoService`.
- `SeoService` sekarang mendukung `forPostIndex()` dan `forTag()`.
- Canonical daftar berita mengikuti query pagination saat `page > 1`.

Gambar frontend:

- Featured image memakai komponen responsive image.
- Hero image memakai `loading="eager"` dan `fetchpriority="high"`.
- Card berita memakai `loading="lazy"`.
- Varian `large`, `medium`, dan `thumbnail` dibuat oleh `PostImageUrlService`, bukan di Blade.
- Jika gambar tidak tersedia, placeholder CSS rasio 16:9 ditampilkan.

Cache frontend:

- Site Settings tetap memakai cache `SiteSettingService`.
- Kategori navigasi memakai cache `public.v2.navigation.category_ids` selama 30 menit.
- Homepage data berita di-cache selama 5 menit dan dibersihkan otomatis saat konten berubah.

Accessibility dasar:

- Skip link ke `#main-content`.
- Header, nav, main, article, section, footer semantik.
- Mobile menu memakai button, `aria-expanded`, dan `aria-controls`.
- Breadcrumb memakai `<nav aria-label="Breadcrumb">` dan ordered list.
- Link fokus memiliki ring yang terlihat.

## Tahap 9: SEO Teknis Publik

Tahap 9 menambahkan endpoint SEO teknis dan pencarian publik.

Endpoint baru:

```text
Pencarian: /cari?q=kata-kunci
Sitemap index: /sitemap.xml
Sitemap post: /sitemaps/posts.xml
Sitemap kategori: /sitemaps/categories.xml
Sitemap tag: /sitemaps/tags.xml
RSS feed: /feed
RSS redirect: /rss.xml -> /feed
Robots: /robots.txt
```

Aturan pencarian:

- Query memakai parameter `q`, minimal 2 karakter dan maksimal 100 karakter.
- Whitespace dan control character dinormalisasi.
- Wildcard SQL `%` dan `_` di-escape agar diperlakukan sebagai teks biasa.
- Hanya post published yang sudah waktunya terbit yang muncul.
- Halaman pencarian memakai `noindex, follow`.

Aturan sitemap dan feed:

- Sitemap dan RSS hanya memuat Post published, tidak soft deleted, `robots_index = true`, dan tanpa canonical eksternal.
- Sitemap kategori hanya memuat kategori aktif yang memiliki post published.
- Sitemap tag hanya memuat tag yang memiliki post published.
- Sitemap memakai content type `application/xml; charset=UTF-8` dan cache 15 menit.
- RSS memakai content type `application/rss+xml; charset=UTF-8`, 20 item terbaru, deskripsi ringkas, dan cache 5 menit.

Robots:

- `APP_ENV=production` mengizinkan halaman publik, menolak `/admin` dan `/filament`, serta menampilkan URL sitemap.
- Environment selain production menolak semua crawler dengan `Disallow: /`.

Structured data dan security headers:

- Home merender `WebSite` dan `NewsMediaOrganization`.
- Detail berita merender `NewsArticle` dan `BreadcrumbList`.
- Listing, kategori, tag, dan search merender `CollectionPage` dan `BreadcrumbList`.
- Halaman HTML publik diberi `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, dan `X-Frame-Options`.

Cache publik:

```text
public.v2.navigation.category_ids
public.v2.homepage
public.v1.sitemap.index
public.v1.sitemap.posts
public.v1.sitemap.categories
public.v1.sitemap.tags
public.v1.feed
```

Cache konten dibersihkan otomatis melalui observer `Post`, `Category`, dan `Tag`.

## Testing dan Verifikasi

Jalankan test:

```bash
php artisan test
```

Jalankan formatter:

```bash
vendor\bin\pint.bat
```

Audit dependency:

```bash
composer audit
npm.cmd audit --audit-level=moderate
```

Cek informasi aplikasi:

```bash
php artisan about
php artisan migrate:status
php artisan route:list
php artisan config:show database
```

Hasil yang diharapkan:

- Homepage dapat dibuka.
- Halaman login Filament dapat dibuka.
- Guest diarahkan ke login saat membuka dashboard.
- Admin development dapat membuka dashboard.
- User yang email-nya tidak terdaftar di `ADMIN_EMAIL` tidak dapat membuka dashboard.
- Migration bawaan Laravel sudah `Ran`.
- Migration portal berita sudah `Ran`.
- Seeder development menghasilkan data contoh portal berita.
- Admin aktif dan Editor aktif dapat mengakses dashboard.
- User nonaktif tidak dapat mengakses dashboard.
- Policy model utama berjalan sesuai role.
- Status transition menolak transisi yang tidak valid.
- Resource Editor hanya menampilkan akun Editor.
- Resource Kategori dan Tag memakai search, filter, sorting, dan relation count.
- Resource Berita membatasi query Editor ke berita miliknya, menyimpan draft, menyimpan relasi tag, dan menjalankan workflow status dari action terpusat.
- Admin dapat memakai soft delete, trashed filter, restore, dan force delete untuk berita.
- Featured image tersimpan di disk `public`, teroptimasi, memiliki variasi ukuran, dan dibersihkan saat force delete.
- Rich text editor menolak konten kosong dan menyimpan HTML yang sudah disanitasi.
- Perubahan slug Post membuat redirect 301, redirect chain dinormalisasi, dan loop ditolak.
- Admin dapat mengelola redirect dan Site Settings, sedangkan Editor ditolak oleh policy/page authorization.
- Site Settings tersimpan dengan typed accessor dan cache dibersihkan setelah update.
- Logo/favicon/default OG image tersimpan di disk `public`.
- SEO Post, Category, dan Home memiliki fallback metadata backend.
- Draft, review, dan scheduled Post menghasilkan noindex melalui `SeoService`.
- Homepage, daftar berita, detail berita, kategori, dan tag publik dapat dibuka sesuai route.
- Halaman publik hanya menampilkan Post published yang waktunya sudah valid.
- Redirect slug lama menghasilkan status 301/302 sesuai data.
- Metadata SEO dasar dirender di layout publik.
- Search publik menampilkan hasil sesuai keyword dan memakai noindex.
- Schema.org JSON-LD dirender pada halaman publik.
- Sitemap XML, RSS feed, dan robots.txt dapat dibuka dengan content type yang benar.
- Cache sitemap, feed, homepage, dan navigasi dibersihkan saat konten berubah.
- Security headers publik muncul pada response HTML.
- Responsive image dan placeholder bekerja pada card dan detail.
- URL create/edit langsung tetap diamankan oleh Policy.
- Cache, session, dan storage dapat ditulis.

## Catatan Shared Hosting cPanel

Untuk production di cPanel:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` isi dengan domain asli.
- Database biasanya memiliki prefix akun cPanel.
- Username database biasanya memiliki prefix akun cPanel.
- User database harus ditambahkan ke database.
- Berikan privilege yang diperlukan ke user database.
- `DB_HOST` biasanya `localhost`, tetapi tetap ikuti informasi provider.
- Document root harus mengarah ke folder `public`.
- Jika symbolic link `public/storage` tidak dapat dibuat melalui SSH, buat link dari panel hosting jika tersedia atau minta provider mengarahkan storage publik sesuai kebijakan hosting. Hindari menyimpan upload langsung ke folder source code.
- Setelah ubah `.env`, jalankan `php artisan optimize:clear`.
- Baca panduan lengkap di `docs/deployment.md`.
- Baca rollback plan di `docs/rollback.md`.
- Baca checklist operasional di `docs/operations.md`.
- Gunakan `docs/release-checklist.md` sebelum go-live.

## Batas Tahap

Tahap saat ini berhenti di finalisasi deployment production shared hosting cPanel, command admin production, dokumentasi deployment/rollback/operasi, environment template production, frontend publik Blade, search publik, sitemap XML, RSS feed, robots.txt dinamis, Schema.org JSON-LD, security headers publik, cache publik dan invalidasinya, homepage, daftar/detail berita, kategori, tag, redirect HTTP slug lama, metadata SEO, responsive image, 404, accessibility dasar, redirect slug backend, Redirect Resource, Site Settings Page, typed settings cache, SEO backend, preview SEO, media upload, rich text editor, sanitasi konten, dan testing.

Jangan dulu membuat:

- Google Analytics.
- Newsletter.
- Komentar pembaca.
- Login pembaca.
- Statistik pembaca.
- Package role dan permission.
- Package media library.
- Galeri berita.
- Video, audio, atau dokumen PDF.
- Cleanup otomatis gambar konten berdasarkan parsing HTML.

Sebelum masuk ke tahap berikutnya, pastikan `php artisan test`, `vendor\bin\pint.bat --test`, dan `npm.cmd run build` berhasil.
