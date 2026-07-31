<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = UserRole::Editor;
        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Editor berhasil dibuat.';
    }
}
