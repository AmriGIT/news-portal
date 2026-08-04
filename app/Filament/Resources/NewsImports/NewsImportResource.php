<?php

namespace App\Filament\Resources\NewsImports;

use App\Enums\NewsImportStatus;
use App\Filament\Resources\NewsImports\Pages\ListNewsImports;
use App\Filament\Resources\NewsImports\Pages\ViewNewsImport;
use App\Models\NewsImport;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class NewsImportResource extends Resource
{
    protected static ?string $model = NewsImport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static string|UnitEnum|null $navigationGroup = 'Import Berita';

    protected static ?string $navigationLabel = 'Riwayat Import';

    protected static ?string $modelLabel = 'Riwayat Import';

    protected static ?string $pluralModelLabel = 'Riwayat Import';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'uuid';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uuid')
                    ->label('UUID')
                    ->copyable()
                    ->searchable(),

                TextColumn::make('importToken.name')
                    ->label('Token')
                    ->placeholder('-'),

                TextColumn::make('requested_publish_mode')
                    ->label('Mode')
                    ->badge(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (NewsImportStatus|string $state): string => $state instanceof NewsImportStatus ? $state->label() : NewsImportStatus::from($state)->label()),

                TextColumn::make('total_items')
                    ->label('Total')
                    ->numeric(),

                TextColumn::make('imported_items')
                    ->label('Imported')
                    ->numeric(),

                TextColumn::make('failed_items')
                    ->label('Failed')
                    ->numeric(),

                TextColumn::make('completed_at')
                    ->label('Selesai')
                    ->dateTime('d M Y H:i')
                    ->timezone(config('app.timezone', 'Asia/Jakarta'))
                    ->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Import')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('uuid')->label('UUID')->copyable(),
                        TextEntry::make('original_filename')->label('File'),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('requested_publish_mode')->label('Mode'),
                        TextEntry::make('total_items')->label('Total'),
                        TextEntry::make('imported_items')->label('Imported'),
                        TextEntry::make('failed_items')->label('Failed'),
                        TextEntry::make('ip_address')->label('IP')->placeholder('-'),
                        TextEntry::make('completed_at')->label('Selesai')->dateTime('d M Y H:i')->placeholder('-'),
                    ]),

                Section::make('Item')
                    ->schema([
                        TextEntry::make('items.title')->label('Judul')->bulleted()->placeholder('-'),
                        TextEntry::make('items.validation_errors')->label('Error')->bulleted()->placeholder('-'),
                    ]),

                Section::make('Sumber')
                    ->schema([
                        TextEntry::make('sources.final_url')->label('URL Sumber')->bulleted()->placeholder('-'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsImports::route('/'),
            'view' => ViewNewsImport::route('/{record}'),
        ];
    }
}
