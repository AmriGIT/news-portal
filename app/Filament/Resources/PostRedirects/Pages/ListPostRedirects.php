<?php

namespace App\Filament\Resources\PostRedirects\Pages;

use App\Filament\Resources\PostRedirects\PostRedirectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPostRedirects extends ListRecords
{
    protected static string $resource = PostRedirectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Redirect'),
        ];
    }
}
