<?php

namespace App\Filament\Resources\PostRedirects;

use App\Filament\Resources\PostRedirects\Pages\CreatePostRedirect;
use App\Filament\Resources\PostRedirects\Pages\EditPostRedirect;
use App\Filament\Resources\PostRedirects\Pages\ListPostRedirects;
use App\Models\PostRedirect;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PostRedirectResource extends Resource
{
    protected static ?string $model = PostRedirect::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;

    protected static string|UnitEnum|null $navigationGroup = 'SEO';

    protected static ?string $navigationLabel = 'Redirect';

    protected static ?string $modelLabel = 'Redirect';

    protected static ?string $pluralModelLabel = 'Redirect';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'source_path';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Redirect')
                    ->columns(2)
                    ->schema([
                        TextInput::make('source_path')
                            ->label('Path Sumber')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Contoh: /berita/url-lama. Path akan dinormalisasi dan tidak boleh memakai domain.'),

                        TextInput::make('destination_path')
                            ->label('Path Tujuan')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Contoh: /berita/url-baru. Hanya path internal.'),

                        Select::make('status_code')
                            ->label('Kode Redirect')
                            ->options([
                                301 => '301 - Permanen',
                                302 => '302 - Sementara',
                            ])
                            ->default(301)
                            ->required()
                            ->native(false),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->required(),

                        Select::make('post_id')
                            ->label('Berita Terkait')
                            ->relationship('post', 'title')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->columnSpanFull()
                            ->helperText('Opsional sebagai relasi informasi. Memilih berita tidak mengubah slug atau path redirect.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source_path')
                    ->label('Path Sumber')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('destination_path')
                    ->label('Path Tujuan')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('status_code')
                    ->label('Kode')
                    ->badge()
                    ->color(fn (int $state): string => $state === 301 ? 'success' : 'warning')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('post.title')
                    ->label('Berita Terkait')
                    ->placeholder('Manual')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('hit_count')
                    ->label('Hit')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('last_hit_at')
                    ->label('Hit Terakhir')
                    ->dateTime('d M Y H:i')
                    ->timezone(config('app.timezone', 'Asia/Jakarta'))
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->timezone(config('app.timezone', 'Asia/Jakarta'))
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->timezone(config('app.timezone', 'Asia/Jakarta'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status_code')
                    ->label('Kode Redirect')
                    ->options([
                        301 => '301 - Permanen',
                        302 => '302 - Sementara',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),

                TernaryFilter::make('post_id')
                    ->label('Tipe')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('post_id'),
                        false: fn (Builder $query): Builder => $query->whereNull('post_id'),
                        blank: fn (Builder $query): Builder => $query,
                    )
                    ->trueLabel('Otomatis')
                    ->falseLabel('Manual'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50])
            ->recordActions([
                EditAction::make()
                    ->label('Edit'),

                DeleteAction::make()
                    ->label('Hapus')
                    ->requiresConfirmation()
                    ->modalDescription('Penghapusan redirect dapat membuat URL lama kembali menjadi 404.')
                    ->successNotificationTitle('Redirect berhasil dihapus.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPostRedirects::route('/'),
            'create' => CreatePostRedirect::route('/create'),
            'edit' => EditPostRedirect::route('/{record}/edit'),
        ];
    }
}
