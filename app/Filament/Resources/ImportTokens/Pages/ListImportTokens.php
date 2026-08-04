<?php

namespace App\Filament\Resources\ImportTokens\Pages;

use App\Filament\Resources\ImportTokens\ImportTokenResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListImportTokens extends ListRecords
{
    protected static string $resource = ImportTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Token Import'),
        ];
    }
}
