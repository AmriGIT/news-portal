<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use JsonException;
use RuntimeException;
use ZipArchive;

class NewsImportZipService
{
    /**
     * @return array{extract_path: string, manifest: array<string, mixed>, posts: array<int, mixed>, sources: array<string, mixed>, warnings: array<int, string>}
     */
    public function extractAndValidate(string $zipPath): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi PHP Zip belum tersedia.');
        }

        $extractPath = storage_path('app/private/imports/api/'.str()->uuid());
        File::ensureDirectoryExists($extractPath);

        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Package ZIP tidak dapat dibuka.');
        }

        try {
            $this->validateEntries($zip);
            $this->extractEntries($zip, $extractPath);
        } finally {
            $zip->close();
        }

        $manifest = $this->jsonFile($extractPath, 'manifest.json');
        $posts = $this->posts($extractPath);
        $sources = $this->jsonFile($extractPath, 'sources.json');
        $warnings = $this->validateManifest($manifest, $posts, $sources, $extractPath);

        return [
            'extract_path' => $extractPath,
            'manifest' => $manifest,
            'posts' => $posts,
            'sources' => $sources,
            'warnings' => $warnings,
        ];
    }

    private function validateEntries(ZipArchive $zip): void
    {
        $maxFiles = (int) config('news-import.max_files', 100);
        $maxUncompressedBytes = (int) config('news-import.max_uncompressed_mb', 200) * 1024 * 1024;
        $totalBytes = 0;

        if ($zip->numFiles > $maxFiles) {
            throw new RuntimeException('Jumlah file di dalam ZIP melebihi batas.');
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            $name = (string) ($stat['name'] ?? '');

            if (str_ends_with($name, '/')) {
                continue;
            }

            if (! $this->isAllowedPath($name)) {
                throw new RuntimeException("Path {$name} tidak diizinkan di dalam ZIP.");
            }

            $size = (int) ($stat['size'] ?? 0);
            $totalBytes += $size;

            if ($totalBytes > $maxUncompressedBytes) {
                throw new RuntimeException('Ukuran ekstraksi ZIP melebihi batas.');
            }
        }
    }

    private function extractEntries(ZipArchive $zip, string $extractPath): void
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = (string) $zip->getNameIndex($index);

            if (str_ends_with($name, '/')) {
                continue;
            }

            $targetPath = $extractPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $name);
            File::ensureDirectoryExists(dirname($targetPath));

            $stream = $zip->getStream($name);

            if ($stream === false) {
                throw new RuntimeException("File {$name} tidak dapat dibaca.");
            }

            File::put($targetPath, stream_get_contents($stream));
            fclose($stream);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonFile(string $extractPath, string $filename): array
    {
        $path = $extractPath.DIRECTORY_SEPARATOR.$filename;

        if (! is_file($path)) {
            throw new RuntimeException("Package wajib berisi {$filename}.");
        }

        try {
            $data = json_decode((string) File::get($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("{$filename} tidak valid: ".$exception->getMessage(), previous: $exception);
        }

        if (! is_array($data)) {
            throw new RuntimeException("{$filename} harus berupa object JSON.");
        }

        return $data;
    }

    /**
     * @return array<int, mixed>
     */
    private function posts(string $extractPath): array
    {
        $data = $this->jsonFile($extractPath, 'posts.json');
        $posts = $data['posts'] ?? $data;

        if (! is_array($posts)) {
            throw new RuntimeException('posts.json harus berisi array posts.');
        }

        $posts = array_values($posts);

        if (count($posts) > (int) config('news-import.max_posts', 20)) {
            throw new RuntimeException('Jumlah artikel melebihi batas import.');
        }

        return $posts;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<int, mixed>  $posts
     * @param  array<string, mixed>  $sources
     * @return array<int, string>
     */
    private function validateManifest(array $manifest, array $posts, array $sources, string $extractPath): array
    {
        if (($manifest['format'] ?? null) !== 'bebasinfo-news-import') {
            throw new RuntimeException('Format manifest tidak didukung.');
        }

        if (! in_array((string) ($manifest['version'] ?? ''), config('news-import.supported_manifest_versions', ['1.0']), true)) {
            throw new RuntimeException('Versi manifest tidak didukung.');
        }

        if ((int) ($manifest['post_count'] ?? count($posts)) !== count($posts)) {
            throw new RuntimeException('post_count di manifest tidak sesuai.');
        }

        $sourceCount = is_array($sources['sources'] ?? null) ? count($sources['sources']) : 0;

        if ((int) ($manifest['source_count'] ?? $sourceCount) !== $sourceCount) {
            throw new RuntimeException('source_count di manifest tidak sesuai.');
        }

        $warnings = [];

        foreach (($manifest['files'] ?? []) as $file) {
            if (! is_array($file) || blank($file['path'] ?? null)) {
                continue;
            }

            $path = (string) $file['path'];

            if (! $this->isAllowedPath($path)) {
                throw new RuntimeException("Path manifest {$path} tidak aman.");
            }

            $fullPath = $extractPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);

            if (! is_file($fullPath)) {
                throw new RuntimeException("File manifest {$path} tidak ditemukan.");
            }

            if (filled($file['sha256'] ?? null) && hash_file('sha256', $fullPath) !== $file['sha256']) {
                throw new RuntimeException("Hash file {$path} tidak sesuai.");
            }

            if (isset($file['size']) && (int) $file['size'] !== filesize($fullPath)) {
                $warnings[] = "Ukuran file {$path} berbeda dari manifest.";
            }
        }

        return $warnings;
    }

    private function isAllowedPath(string $path): bool
    {
        $path = str_replace('\\', '/', trim($path));
        $segments = array_filter(explode('/', $path), fn (string $segment): bool => $segment !== '');

        if ($path === '' || str_contains($path, "\0") || str_starts_with($path, '/') || preg_match('/^[a-zA-Z]:\//', $path) === 1 || in_array('..', $segments, true)) {
            return false;
        }

        if (in_array($path, config('news-import.allowed_package_files', []), true)) {
            return true;
        }

        if (! str_starts_with($path, 'images/')) {
            return false;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, config('news-import.allowed_image_extensions', []), true);
    }
}
