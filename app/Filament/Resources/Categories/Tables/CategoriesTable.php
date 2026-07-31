<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Models\Category;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                    ->falseIcon(Heroicon::OutlinedXCircle)
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),

                TextColumn::make('posts_count')
                    ->label('Jumlah Berita')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('posts_count', $direction)),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query->orderBy('sort_order')->orderBy('name'))
            ->recordActions([
                EditAction::make()
                    ->label('Edit'),

                DeleteAction::make()
                    ->label('Hapus')
                    ->before(function (Action $action, Category $record): void {
                        if ($record->posts()->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('Kategori tidak dapat dihapus karena masih digunakan oleh berita.')
                                ->send();

                            $action->halt();
                        }
                    })
                    ->successNotificationTitle('Kategori berhasil dihapus.'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->before(function (Action $action, Collection $records): void {
                        if ($records->contains(fn (Category $record): bool => $record->posts()->exists())) {
                            Notification::make()
                                ->danger()
                                ->title('Beberapa kategori tidak dapat dihapus karena masih digunakan oleh berita.')
                                ->send();

                            $action->halt();
                        }
                    }),
            ]);
    }
}
