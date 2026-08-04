<?php

namespace App\Filament\Resources\ImportTokens;

use App\Filament\Resources\ImportTokens\Pages\CreateImportToken;
use App\Filament\Resources\ImportTokens\Pages\ListImportTokens;
use App\Models\ImportToken;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ImportTokenResource extends Resource
{
    protected static ?string $model = ImportToken::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Import Berita';

    protected static ?string $navigationLabel = 'Import Tokens';

    protected static ?string $modelLabel = 'Import Token';

    protected static ?string $pluralModelLabel = 'Import Tokens';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Token')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Token')
                            ->required()
                            ->maxLength(255),

                        Select::make('user_id')
                            ->label('Author / Service Account')
                            ->relationship(
                                'user',
                                'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('is_active', true)
                                    ->orderBy('name'),
                            )
                            ->searchable()
                            ->preload()
                            ->required(),

                        CheckboxList::make('abilities')
                            ->label('Abilities')
                            ->options([
                                'news:import' => 'Import artikel',
                                'news:publish' => 'Publish artikel',
                            ])
                            ->default(['news:import'])
                            ->required()
                            ->helperText('Scope publish memberi izin membuat artikel langsung terbit.')
                            ->columnSpanFull(),

                        DateTimePicker::make('expires_at')
                            ->label('Kedaluwarsa')
                            ->timezone(config('app.timezone', 'Asia/Jakarta'))
                            ->default(now()->addDays((int) config('news-import.token_expiry_days', 90)))
                            ->native(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Author')
                    ->sortable(),

                TextColumn::make('abilities')
                    ->label('Abilities')
                    ->badge(),

                TextColumn::make('expires_at')
                    ->label('Kedaluwarsa')
                    ->dateTime('d M Y H:i')
                    ->timezone(config('app.timezone', 'Asia/Jakarta'))
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('last_used_at')
                    ->label('Terakhir Dipakai')
                    ->dateTime('d M Y H:i')
                    ->timezone(config('app.timezone', 'Asia/Jakarta'))
                    ->placeholder('-')
                    ->sortable(),

                IconColumn::make('revoked_at')
                    ->label('Revoked')
                    ->getStateUsing(fn (ImportToken $record): bool => $record->revoked_at !== null)
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('revoke')
                    ->label('Revoke')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ImportToken $record): bool => $record->revoked_at === null)
                    ->action(fn (ImportToken $record): bool => $record->forceFill(['revoked_at' => now()])->save()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImportTokens::route('/'),
            'create' => CreateImportToken::route('/create'),
        ];
    }
}
