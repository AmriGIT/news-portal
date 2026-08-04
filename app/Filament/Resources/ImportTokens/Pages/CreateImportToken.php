<?php

namespace App\Filament\Resources\ImportTokens\Pages;

use App\Filament\Resources\ImportTokens\ImportTokenResource;
use App\Models\User;
use App\Services\NewsImportTokenService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CreateImportToken extends CreateRecord
{
    protected static string $resource = ImportTokenResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $creator = auth()->user();

        if (! $creator instanceof User) {
            throw new RuntimeException('User admin tidak tersedia.');
        }

        $result = app(NewsImportTokenService::class)->create(
            name: (string) $data['name'],
            creator: $creator,
            user: User::query()->find($data['user_id']),
            abilities: $data['abilities'] ?? ['news:import'],
            expiresAt: filled($data['expires_at'] ?? null) ? Carbon::parse($data['expires_at']) : null,
        );

        Notification::make()
            ->success()
            ->title('Token import berhasil dibuat.')
            ->body('Salin sekarang, token tidak akan ditampilkan ulang: '.$result['plain_text_token'])
            ->persistent()
            ->send();

        return $result['token'];
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }
}
