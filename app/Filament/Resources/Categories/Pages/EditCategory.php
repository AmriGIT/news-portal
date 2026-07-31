<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Hapus')
                ->before(function (Action $action, Category $record): void {
                    if ($record->posts()->exists()) {
                        Notification::make()
                            ->danger()
                            ->title('Kategori tidak dapat dihapus karena masih digunakan oleh berita.')
                            ->send();

                        $action->halt();
                    }
                })
                ->successNotificationTitle('Kategori berhasil dihapus.'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Kategori berhasil diperbarui.';
    }
}
