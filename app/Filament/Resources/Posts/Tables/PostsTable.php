<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->limit(60)
                    ->url(fn (Post $record): string => PostResource::getUrl('view', ['record' => $record])),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('author.name')
                    ->label('Penulis')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('editor.name')
                    ->label('Peninjau')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (PostStatus|string $state): string => $state instanceof PostStatus ? $state->label() : PostStatus::from($state)->label())
                    ->color(fn (PostStatus|string $state): string => match ($state instanceof PostStatus ? $state : PostStatus::from($state)) {
                        PostStatus::Draft => 'gray',
                        PostStatus::Review => 'info',
                        PostStatus::Scheduled => 'warning',
                        PostStatus::Published => 'success',
                        PostStatus::Archived => 'danger',
                    })
                    ->searchable(),

                IconColumn::make('is_featured')
                    ->label('Unggulan')
                    ->boolean(),

                TextColumn::make('tags_count')
                    ->label('Jumlah Tag')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('tags_count', $direction))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('published_at')
                    ->label('Waktu Publikasi')
                    ->dateTime('d M Y H:i')
                    ->timezone(config('app.timezone', 'Asia/Jakarta'))
                    ->placeholder('-')
                    ->sortable(),

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

                TextColumn::make('deleted_at')
                    ->label('Dihapus')
                    ->dateTime('d M Y H:i')
                    ->timezone(config('app.timezone', 'Asia/Jakarta'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(PostStatus::cases())->mapWithKeys(fn (PostStatus $status): array => [
                        $status->value => $status->label(),
                    ])->all()),

                SelectFilter::make('category')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('author')
                    ->label('Penulis')
                    ->relationship('author', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),

                TernaryFilter::make('is_featured')
                    ->label('Berita Unggulan')
                    ->trueLabel('Unggulan')
                    ->falseLabel('Bukan Unggulan'),

                Filter::make('published_at')
                    ->label('Waktu Publikasi')
                    ->schema([
                        DatePicker::make('published_from')
                            ->label('Dari Tanggal'),
                        DatePicker::make('published_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['published_from'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('published_at', '>=', $date),
                        )
                        ->when(
                            $data['published_until'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('published_at', '<=', $date),
                        )),

                TrashedFilter::make()
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat'),

                EditAction::make()
                    ->label('Edit'),

                ...PostResource::getWorkflowActions(),

                DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn (Post $record): bool => auth()->user()?->can('delete', $record) ?? false)
                    ->successNotificationTitle('Berita berhasil dihapus.'),

                RestoreAction::make()
                    ->label('Pulihkan')
                    ->visible(fn (Post $record): bool => auth()->user()?->can('restore', $record) ?? false)
                    ->successNotificationTitle('Berita berhasil dipulihkan.'),

                ForceDeleteAction::make()
                    ->label('Hapus Permanen')
                    ->visible(fn (Post $record): bool => auth()->user()?->can('forceDelete', $record) ?? false)
                    ->successNotificationTitle('Berita berhasil dihapus permanen.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus Terpilih'),
                    RestoreBulkAction::make()
                        ->label('Pulihkan Terpilih'),
                    ForceDeleteBulkAction::make()
                        ->label('Hapus Permanen Terpilih'),
                ])
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
            ]);
    }
}
