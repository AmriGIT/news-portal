<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use RuntimeException;
use Throwable;

class PostImageService
{
    /**
     * @return array<string, string>
     */
    public function storeFeaturedImage(UploadedFile $file): array
    {
        $this->ensureImageCanBeProcessed();
        $this->validateImageFile($file);

        $manager = $this->manager();
        $image = $manager->decodePath($file->getRealPath());
        $extension = $this->outputExtension();
        $directory = $this->datedDirectory(config('media.featured.directory'));
        $filename = (string) Str::uuid();
        $paths = [
            'original' => "{$directory}/{$filename}.{$extension}",
            'large' => "{$directory}/{$filename}-large.{$extension}",
            'medium' => "{$directory}/{$filename}-medium.{$extension}",
            'thumbnail' => "{$directory}/{$filename}-thumbnail.{$extension}",
        ];

        try {
            foreach ($paths as $variant => $path) {
                [$width, $height] = config("media.featured.sizes.{$variant}");

                $processed = (clone $image)->cover($width, $height);
                $this->putEncodedImage($path, $processed);
            }
        } catch (Throwable $exception) {
            $this->deleteMany($paths);

            Log::warning('Featured image processing failed.', [
                'disk' => $this->diskName(),
                'paths' => $paths,
                'exception' => $exception::class,
            ]);

            throw new RuntimeException('Gambar tidak dapat diproses.', previous: $exception);
        }

        return $paths;
    }

    public function storeContentImage(UploadedFile $file): string
    {
        $this->ensureImageCanBeProcessed();
        $this->validateImageFile($file);

        $extension = $this->outputExtension();
        $path = $this->datedDirectory(config('media.content.directory')).'/'.Str::uuid().".{$extension}";

        try {
            $image = $this->manager()
                ->decodePath($file->getRealPath())
                ->scaleDown(width: (int) config('media.content.max_width', 1600));

            $this->putEncodedImage($path, $image);
        } catch (Throwable $exception) {
            $this->delete($path);

            Log::warning('Content image processing failed.', [
                'disk' => $this->diskName(),
                'path' => $path,
                'exception' => $exception::class,
            ]);

            throw new RuntimeException('Gambar tidak dapat diproses.', previous: $exception);
        }

        return $path;
    }

    /**
     * @return array<string>
     */
    public function variantPaths(string $path): array
    {
        $info = pathinfo($path);
        $directory = $info['dirname'] === '.' ? '' : $info['dirname'].'/';
        $filename = $info['filename'];
        $extension = $info['extension'] ?? $this->outputExtension();

        return [
            $path,
            "{$directory}{$filename}-large.{$extension}",
            "{$directory}{$filename}-medium.{$extension}",
            "{$directory}{$filename}-thumbnail.{$extension}",
        ];
    }

    public function deleteWithVariants(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        $this->deleteMany($this->variantPaths($path));
    }

    public function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        try {
            $this->disk()->delete($path);
        } catch (Throwable $exception) {
            Log::warning('Image deletion failed.', [
                'disk' => $this->diskName(),
                'path' => $path,
                'exception' => $exception::class,
            ]);
        }
    }

    /**
     * @param  iterable<string>  $paths
     */
    public function deleteMany(iterable $paths): void
    {
        foreach ($paths as $path) {
            $this->delete($path);
        }
    }

    private function putEncodedImage(string $path, ImageInterface $image): void
    {
        $encoded = $image->encodeUsingFormat($this->outputFormat(), quality: (int) config('media.featured.quality', 82));

        if (! $this->disk()->put($path, (string) $encoded, ['visibility' => 'public'])) {
            throw new RuntimeException('Penyimpanan gambar tidak dapat diakses.');
        }
    }

    private function validateImageFile(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw new RuntimeException('Temporary file tidak ditemukan.');
        }

        if ($file->getSize() > ((int) config('media.featured.max_size', 5120) * 1024)) {
            throw new RuntimeException('Ukuran gambar maksimal 5 MB.');
        }

        if (! in_array($file->getMimeType(), config('media.accepted_mime_types', []), true)) {
            throw new RuntimeException('Format gambar harus JPG, JPEG, PNG, atau WebP.');
        }

        $dimensions = @getimagesize($file->getRealPath());

        if ($dimensions === false) {
            throw new RuntimeException('Gambar tidak dapat diproses.');
        }

    }

    private function ensureImageCanBeProcessed(): void
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('GD tidak tersedia.');
        }

        if ($this->shouldOutputWebp() && ! function_exists('imagewebp')) {
            throw new RuntimeException('WebP tidak didukung oleh GD.');
        }
    }

    private function manager(): ImageManager
    {
        return ImageManager::usingDriver(GdDriver::class);
    }

    private function diskName(): string
    {
        return config('media.disk', 'public');
    }

    private function disk()
    {
        return Storage::disk($this->diskName());
    }

    private function datedDirectory(string $baseDirectory): string
    {
        return trim($baseDirectory, '/').'/'.now()->format('Y/m');
    }

    private function outputFormat(): Format
    {
        return $this->shouldOutputWebp() ? Format::WEBP : Format::JPEG;
    }

    private function outputExtension(): string
    {
        return $this->shouldOutputWebp() ? 'webp' : 'jpg';
    }

    private function shouldOutputWebp(): bool
    {
        return function_exists('imagewebp');
    }
}
