<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class PostImportTemplateController extends Controller
{
    public function __invoke()
    {
        abort_unless(auth()->user()?->isAdmin(), Response::HTTP_FORBIDDEN);

        if (! class_exists(ZipArchive::class)) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Ekstensi PHP Zip belum tersedia.');
        }

        $directory = storage_path('app/private/imports/templates');
        $zipPath = $directory.'/template-import-berita-'.Str::uuid().'.zip';
        $sampleImagePath = public_path('images/default.png');

        File::ensureDirectoryExists($directory);

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Template import tidak dapat dibuat.');
        }

        $zip->addFromString('posts.json', $this->postsJson());

        if (is_file($sampleImagePath)) {
            $zip->addFile($sampleImagePath, 'images/berita-utama.png');
            $zip->addFile($sampleImagePath, 'images/konten-1.png');
        }

        $zip->addFromString('README.txt', $this->readme());
        $zip->close();

        return response()
            ->download($zipPath, 'template-import-berita-bebasinfo.zip')
            ->deleteFileAfterSend(true);
    }

    private function postsJson(): string
    {
        return json_encode([
            'posts' => [
                [
                    'title' => 'Contoh Berita Import BebasInfo',
                    'slug' => 'contoh-berita-import-bebasinfo',
                    'excerpt' => 'Ringkasan singkat berita import untuk preview daftar dan SEO.',
                    'content' => '<p>Isi berita import dapat memakai HTML sederhana.</p><p><img src="images/konten-1.png" alt="Foto pendukung berita"></p>',
                    'category' => 'Nasional',
                    'tags' => ['Import', 'BebasInfo'],
                    'status' => 'draft',
                    'published_at' => null,
                    'is_featured' => false,
                    'featured_image' => 'images/berita-utama.png',
                    'featured_image_alt' => 'Ilustrasi berita utama BebasInfo',
                    'featured_image_caption' => 'Keterangan foto dapat dikosongkan jika tidak diperlukan.',
                    'featured_image_credit' => 'BebasInfo',
                    'seo_title' => 'Contoh Berita Import BebasInfo',
                    'seo_description' => 'Deskripsi SEO berita import sekitar 150 sampai 160 karakter agar terbaca baik di mesin pencari.',
                    'robots_index' => true,
                    'robots_follow' => true,
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function readme(): string
    {
        return implode("\n", [
            'Template Import Berita BebasInfo',
            '',
            'Cara pakai:',
            '1. Edit posts.json.',
            '2. Ganti atau tambah file gambar di folder images/.',
            '3. Pastikan featured_image mengarah ke path relatif di ZIP, contoh images/berita-utama.png.',
            '4. Upload ZIP lewat Admin > Berita > Import Berita.',
            '',
            'Aturan featured image:',
            '- JPG, JPEG, PNG, atau WebP.',
            '- Maksimal 5 MB.',
            '- Resolusi minimal 1200 x 675 piksel.',
            '- Jika featured_image dikosongkan, frontend memakai default image.',
            '',
        ]);
    }
}
