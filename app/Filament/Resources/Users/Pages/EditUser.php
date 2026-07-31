<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['role'] = UserRole::Editor;

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Editor berhasil diperbarui.';
    }
}
