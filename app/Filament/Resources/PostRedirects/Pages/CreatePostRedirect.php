<?php

namespace App\Filament\Resources\PostRedirects\Pages;

use App\Filament\Resources\PostRedirects\PostRedirectResource;
use App\Services\RedirectService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CreatePostRedirect extends CreateRecord
{
    protected static string $resource = PostRedirectResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            return app(RedirectService::class)->normalizeData($data);
        } catch (InvalidArgumentException $exception) {
            $field = str_contains($exception->getMessage(), 'Kode redirect') ? 'status_code' : 'source_path';

            throw ValidationException::withMessages([
                'data.'.$field => $exception->getMessage(),
            ]);
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Redirect berhasil dibuat.';
    }
}
