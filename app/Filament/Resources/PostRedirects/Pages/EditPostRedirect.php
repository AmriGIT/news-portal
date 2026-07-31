<?php

namespace App\Filament\Resources\PostRedirects\Pages;

use App\Filament\Resources\PostRedirects\PostRedirectResource;
use App\Services\RedirectService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class EditPostRedirect extends EditRecord
{
    protected static string $resource = PostRedirectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Hapus')
                ->requiresConfirmation()
                ->successNotificationTitle('Redirect berhasil dihapus.'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        try {
            return app(RedirectService::class)->normalizeData($data, $this->record->id);
        } catch (InvalidArgumentException $exception) {
            $field = str_contains($exception->getMessage(), 'Kode redirect') ? 'status_code' : 'source_path';

            throw ValidationException::withMessages([
                'data.'.$field => $exception->getMessage(),
            ]);
        }
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Redirect berhasil diperbarui.';
    }
}
