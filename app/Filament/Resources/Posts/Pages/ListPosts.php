<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use App\Services\PostImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_import_template')
                ->label('Download Template')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                ->url(fn (): string => route('admin.posts.import-template')),

            Action::make('import_posts')
                ->label('Import Berita')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                ->modalHeading('Import Berita dari ZIP')
                ->modalSubmitActionLabel('Import')
                ->schema([
                    FileUpload::make('archive')
                        ->label('File ZIP')
                        ->disk('local')
                        ->directory('imports/posts')
                        ->acceptedFileTypes([
                            'application/zip',
                            'application/x-zip-compressed',
                            'multipart/x-zip',
                        ])
                        ->maxSize(51200)
                        ->required()
                        ->helperText('Upload ZIP berisi posts.json di root dan gambar di folder images/. Gambar utama direferensikan dari field featured_image, contoh: images/berita-1.jpg.'),
                ])
                ->action(function (array $data, Action $action): void {
                    $path = $this->uploadedArchivePath($data['archive'] ?? null);

                    if ($path === null) {
                        Notification::make()
                            ->danger()
                            ->title('File ZIP import tidak ditemukan.')
                            ->send();

                        $action->failure();
                        $action->halt();
                    }

                    try {
                        $result = app(PostImportService::class)->importFromZip(
                            Storage::disk('local')->path($path),
                            auth()->user(),
                        );

                        $notification = Notification::make()
                            ->title("Import selesai: {$result['imported']} berita berhasil, {$result['failed']} gagal.");

                        if ($result['failed'] > 0) {
                            $notification
                                ->warning()
                                ->body(collect($result['failures'])->take(5)->implode("\n"));
                        } else {
                            $notification->success();
                        }

                        $notification->send();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Import berita gagal.')
                            ->body($exception->getMessage())
                            ->send();

                        $action->failure();
                        $action->halt();
                    } finally {
                        if (isset($path)) {
                            Storage::disk('local')->delete($path);
                        }
                    }
                }),

            CreateAction::make()
                ->label('Buat Berita'),
        ];
    }

    private function uploadedArchivePath(mixed $state): ?string
    {
        if (is_array($state)) {
            $state = collect($state)->filter()->first();
        }

        return filled($state) ? (string) $state : null;
    }
}
