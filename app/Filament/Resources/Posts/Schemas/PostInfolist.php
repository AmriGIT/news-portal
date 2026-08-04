<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Services\PostImageUrlService;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PostInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Konten Utama')
                    ->schema([
                        TextEntry::make('title')
                            ->label('Judul Berita')
                            ->columnSpanFull(),

                        TextEntry::make('slug')
                            ->label('Slug'),

                        TextEntry::make('excerpt')
                            ->label('Ringkasan')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('content')
                            ->label('Isi Berita')
                            ->columnSpanFull(),
                    ]),

                Section::make('Klasifikasi')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('category.name')
                            ->label('Kategori'),

                        TextEntry::make('author.name')
                            ->label('Penulis'),

                        TextEntry::make('editor.name')
                            ->label('Peninjau')
                            ->placeholder('-'),

                        TextEntry::make('tags.name')
                            ->label('Tag')
                            ->badge()
                            ->placeholder('-')
                            ->columnSpanFull(),

                        IconEntry::make('is_featured')
                            ->label('Berita Unggulan')
                            ->boolean(),
                    ]),

                Section::make('Publikasi')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (PostStatus|string $state): string => $state instanceof PostStatus ? $state->label() : PostStatus::from($state)->label())
                            ->color(fn (PostStatus|string $state): string => match ($state instanceof PostStatus ? $state : PostStatus::from($state)) {
                                PostStatus::Draft => 'gray',
                                PostStatus::Review => 'info',
                                PostStatus::Scheduled => 'warning',
                                PostStatus::Published => 'success',
                                PostStatus::Archived => 'danger',
                            }),

                        TextEntry::make('published_at')
                            ->label('Waktu Publikasi')
                            ->dateTime('d M Y H:i')
                            ->timezone(config('app.timezone', 'Asia/Jakarta'))
                            ->placeholder('-'),

                        TextEntry::make('deleted_at')
                            ->label('Dihapus')
                            ->dateTime('d M Y H:i')
                            ->timezone(config('app.timezone', 'Asia/Jakarta'))
                            ->visible(fn (Post $record): bool => $record->trashed()),
                    ]),

                Section::make('SEO')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('seo_title')
                            ->label('Judul SEO')
                            ->placeholder('-'),

                        TextEntry::make('canonical_url')
                            ->label('URL Canonical')
                            ->placeholder('-'),

                        TextEntry::make('seo_description')
                            ->label('Deskripsi SEO')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('og_image')
                            ->label('Gambar Open Graph')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        IconEntry::make('robots_index')
                            ->label('Robots Index')
                            ->boolean(),

                        IconEntry::make('robots_follow')
                            ->label('Robots Follow')
                            ->boolean(),
                    ]),

                Section::make('Gambar')
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('featured_image')
                            ->label('Preview Featured Image')
                            ->disk(config('media.disk', 'public'))
                            ->visibility('public')
                            ->defaultImageUrl(fn (): string => app(PostImageUrlService::class)->original(null))
                            ->alt(fn (Post $record): string => app(PostImageUrlService::class)->alt($record->featured_image_alt, $record->title))
                            ->imageWidth('min(100%, 42rem)')
                            ->imageHeight('auto')
                            ->extraImgAttributes([
                                'class' => 'rounded-lg object-cover',
                            ])
                            ->openUrlInNewTab()
                            ->url(fn (Post $record): string => app(PostImageUrlService::class)->original($record->featured_image))
                            ->columnSpanFull(),

                        TextEntry::make('featured_image')
                            ->label('Path Featured Image')
                            ->placeholder('-'),

                        TextEntry::make('featured_image_alt')
                            ->label('Alt Text')
                            ->placeholder('-'),

                        TextEntry::make('featured_image_caption')
                            ->label('Caption')
                            ->placeholder('-'),

                        TextEntry::make('featured_image_credit')
                            ->label('Credit')
                            ->placeholder('-'),

                        TextEntry::make('detail_images')
                            ->label('Gambar Detail')
                            ->formatStateUsing(fn (mixed $state): string => collect($state ?? [])->filter()->implode("\n"))
                            ->listWithLineBreaks()
                            ->placeholder('Memakai featured image')
                            ->columnSpanFull(),
                    ]),

                Section::make('Audit')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime('d M Y H:i')
                            ->timezone(config('app.timezone', 'Asia/Jakarta')),

                        TextEntry::make('updated_at')
                            ->label('Diperbarui')
                            ->dateTime('d M Y H:i')
                            ->timezone(config('app.timezone', 'Asia/Jakarta')),
                    ]),
            ]);
    }
}
