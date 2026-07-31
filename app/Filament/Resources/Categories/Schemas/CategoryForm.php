<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use App\Services\SeoService;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state): void {
                        if (($get('slug') ?? '') !== Str::slug($old ?? '')) {
                            return;
                        }

                        $set('slug', Str::slug($state ?? ''));
                    })
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? trim($state) : null),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->alphaDash()
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::slug($state) : null),

                Textarea::make('description')
                    ->label('Deskripsi')
                    ->maxLength(1000)
                    ->rows(4)
                    ->columnSpanFull(),

                TextInput::make('seo_title')
                    ->label('SEO Title')
                    ->maxLength(70)
                    ->helperText('Digunakan sebagai judul SEO jika kategori ditampilkan di halaman publik.'),

                Textarea::make('seo_description')
                    ->label('SEO Description')
                    ->maxLength(170)
                    ->rows(3)
                    ->helperText('Ringkasan singkat untuk meta description.')
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->required(),

                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->required(),

                Placeholder::make('seo_preview')
                    ->label('Preview SEO')
                    ->content(fn (Get $get, ?Category $record): HtmlString => self::seoPreview($get, $record))
                    ->columnSpanFull(),
            ]);
    }

    private static function seoPreview(Get $get, ?Category $record): HtmlString
    {
        $category = $record ? $record->replicate() : new Category;
        $category->forceFill([
            'name' => $get('name') ?: $record?->name ?: 'Kategori',
            'slug' => $get('slug') ?: $record?->slug ?: 'kategori',
            'description' => $get('description') ?: $record?->description,
            'seo_title' => $get('seo_title') ?: $record?->seo_title,
            'seo_description' => $get('seo_description') ?: $record?->seo_description,
            'is_active' => (bool) ($get('is_active') ?? $record?->is_active ?? true),
        ]);

        $seo = app(SeoService::class)->forCategory($category);
        $robots = ($seo->robotsIndex ? 'index' : 'noindex').', '.($seo->robotsFollow ? 'follow' : 'nofollow');

        return new HtmlString(
            '<div class="space-y-1 text-sm">'.
            '<div><strong>'.e($seo->title).'</strong></div>'.
            '<div>'.e($seo->canonicalUrl).'</div>'.
            '<div>'.e($seo->description ?: '-').'</div>'.
            '<div>Robots: '.e($robots).'</div>'.
            '</div>'
        );
    }
}
