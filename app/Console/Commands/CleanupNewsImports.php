<?php

namespace App\Console\Commands;

use App\Models\NewsImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanupNewsImports extends Command
{
    protected $signature = 'news-imports:cleanup {--hours=24 : Usia minimum file temporary yang boleh dihapus}';

    protected $description = 'Hapus temporary file import berita dan audit log lama.';

    public function handle(): int
    {
        $deletedFiles = $this->cleanupTemporaryFiles((int) $this->option('hours'));
        $deletedLogs = $this->cleanupOldLogs();

        $this->components->info("Cleanup import selesai. Temporary items: {$deletedFiles}, audit logs: {$deletedLogs}.");

        return self::SUCCESS;
    }

    private function cleanupOldLogs(): int
    {
        $retentionDays = (int) config('news-import.log_retention_days', 90);

        if ($retentionDays <= 0) {
            return 0;
        }

        return NewsImport::query()
            ->where('created_at', '<', now()->subDays($retentionDays))
            ->delete();
    }

    private function cleanupTemporaryFiles(int $hours): int
    {
        $basePath = storage_path('app/private/imports/api');

        if (! File::isDirectory($basePath)) {
            return 0;
        }

        $threshold = now()->subHours(max(1, $hours))->getTimestamp();
        $deleted = 0;

        foreach (File::allFiles($basePath) as $file) {
            if ($file->getMTime() > $threshold) {
                continue;
            }

            File::delete($file->getRealPath());
            $deleted++;
        }

        foreach (array_reverse(File::directories($basePath)) as $directory) {
            if ($this->isEmptyDirectory($directory)) {
                File::deleteDirectory($directory);
                $deleted++;
            }
        }

        return $deleted;
    }

    private function isEmptyDirectory(string $directory): bool
    {
        return File::isDirectory($directory) && count(File::files($directory)) === 0 && count(File::directories($directory)) === 0;
    }
}
