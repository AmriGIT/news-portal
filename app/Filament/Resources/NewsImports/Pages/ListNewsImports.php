<?php

namespace App\Filament\Resources\NewsImports\Pages;

use App\Filament\Resources\NewsImports\NewsImportResource;
use Filament\Resources\Pages\ListRecords;

class ListNewsImports extends ListRecords
{
    protected static string $resource = NewsImportResource::class;
}
