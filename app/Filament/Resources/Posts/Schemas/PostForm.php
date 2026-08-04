<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Post;
use App\Rules\MeaningfulRichText;
use App\Services\ContentSanitizer;
use App\Services\PostImageService;
use App\Services\SeoService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Konten Utama')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul Berita')
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
                            ->helperText('Perubahan slug dapat memengaruhi URL berita.')
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::slug($state) : null),

                        Textarea::make('excerpt')
                            ->label('Ringkasan')
                            ->maxLength(500)
                            ->rows(4)
                            ->columnSpanFull(),

                        RichEditor::make('content')
                            ->label('Isi Berita')
                            ->required()
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'link'],
                                ['h2', 'h3', 'h4'],
                                ['blockquote', 'bulletList', 'orderedList'],
                                ['attachFiles'],
                                ['undo', 'redo'],
                            ])
                            ->fileAttachments(true)
                            ->fileAttachmentsDisk(config('media.disk', 'public'))
                            ->fileAttachmentsDirectory(fn (): string => trim(config('media.content.directory'), '/').'/'.now()->format('Y/m'))
                            ->fileAttachmentsVisibility('public')
                            ->fileAttachmentsAcceptedFileTypes(config('media.accepted_mime_types'))
                            ->fileAttachmentsMaxSize((int) config('media.content.max_size', 5120))
                            ->saveUploadedFileAttachmentUsing(fn (TemporaryUploadedFile $file): string => app(PostImageService::class)->storeContentImage($file))
                            ->preventFileAttachmentPathTampering()
                            ->rule(new MeaningfulRichText)
                            ->dehydrateStateUsing(fn (mixed $state): string => app(ContentSanitizer::class)->sanitize($state))
                            ->columnSpanFull()
                            ->helperText('Konten disimpan sebagai HTML yang disanitasi. Rendering frontend tetap dapat menambahkan sanitasi sebagai lapisan tambahan.'),
                    ]),

                Section::make('Klasifikasi')
                    ->columns(2)
                    ->schema([
                        Select::make('category_id')
                            ->label('Kategori')
                            ->relationship(
                                name: 'category',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('is_active', true)
                                    ->orderBy('name'),
                            )
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('tags')
                            ->label('Tag')
                            ->relationship('tags', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->orderBy('name'))
                            ->multiple()
                            ->searchable()
                            ->preload(),

                        Select::make('author_id')
                            ->label('Penulis')
                            ->relationship(
                                name: 'author',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('is_active', true)
                                    ->whereIn('role', [UserRole::Admin->value, UserRole::Editor->value])
                                    ->orderBy('name'),
                            )
                            ->searchable()
                            ->preload()
                            ->default(fn (): ?int => auth()->id())
                            ->required(fn (): bool => auth()->user()?->isAdmin() ?? false)
                            ->hidden(fn (): bool => auth()->user()?->isEditor() ?? false),

                        Select::make('editor_id')
                            ->label('Peninjau')
                            ->relationship(
                                name: 'editor',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('is_active', true)
                                    ->whereIn('role', [UserRole::Admin->value, UserRole::Editor->value])
                                    ->orderBy('name'),
                            )
                            ->searchable()
                            ->preload()
                            ->hidden(fn (): bool => auth()->user()?->isEditor() ?? false),

                        Toggle::make('is_featured')
                            ->label('Berita Unggulan')
                            ->default(false)
                            ->disabled(fn (): bool => auth()->user()?->isEditor() ?? true)
                            ->dehydrated(fn (): bool => auth()->user()?->isAdmin() ?? false),
                    ]),

                Section::make('Publikasi')
                    ->columns(2)
                    ->schema([
                        Placeholder::make('status_label')
                            ->label('Status')
                            ->content(fn (?Post $record): string => $record?->status?->label() ?? PostStatus::Draft->label()),

                        DateTimePicker::make('published_at')
                            ->label('Waktu Publikasi')
                            ->timezone(config('app.timezone', 'Asia/Jakarta'))
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('-')
                            ->helperText('Waktu mengikuti timezone aplikasi Asia/Jakarta dan diubah melalui action workflow.'),
                    ]),

                Section::make('SEO')
                    ->columns(2)
                    ->schema([
                        TextInput::make('seo_title')
                            ->label('Judul SEO')
                            ->maxLength(255)
                            ->helperText('Rekomendasi sekitar 60 karakter. Jika kosong, frontend memakai judul berita.'),

                        TextInput::make('canonical_url')
                            ->label('URL Canonical')
                            ->url()
                            ->maxLength(255)
                            ->rule(function () {
                                return function (string $attribute, mixed $value, \Closure $fail): void {
                                    if (blank($value)) {
                                        return;
                                    }

                                    $value = trim((string) $value);
                                    $scheme = parse_url($value, PHP_URL_SCHEME);

                                    if (preg_match('/\s/', $value) || ! in_array($scheme, ['http', 'https'], true) || filter_var($value, FILTER_VALIDATE_URL) === false) {
                                        $fail('URL canonical harus absolut dengan scheme HTTP atau HTTPS.');
                                    }
                                };
                            })
                            ->helperText('Boleh eksternal untuk konten sindikasi, tetapi versi eksternal dapat dianggap sebagai sumber utama.'),

                        Textarea::make('seo_description')
                            ->label('Deskripsi SEO')
                            ->maxLength(1000)
                            ->rows(3)
                            ->helperText('Rekomendasi sekitar 160 karakter. Jika kosong, frontend memakai ringkasan.')
                            ->columnSpanFull(),

                        TextInput::make('og_image')
                            ->label('Gambar Open Graph')
                            ->maxLength(255)
                            ->helperText('Path atau URL sementara. Upload media dibuat pada tahap berikutnya.')
                            ->columnSpanFull(),

                        Toggle::make('robots_index')
                            ->label('Izinkan Mesin Pencari Mengindeks')
                            ->default(true)
                            ->disabled(fn (): bool => auth()->user()?->isEditor() ?? true)
                            ->dehydrated(fn (): bool => auth()->user()?->isAdmin() ?? false),

                        Toggle::make('robots_follow')
                            ->label('Izinkan Mesin Pencari Mengikuti Tautan')
                            ->default(true)
                            ->disabled(fn (): bool => auth()->user()?->isEditor() ?? true)
                            ->dehydrated(fn (): bool => auth()->user()?->isAdmin() ?? false),

                        Placeholder::make('seo_preview')
                            ->label('Preview SEO')
                            ->content(fn (Get $get, ?Post $record): HtmlString => self::seoPreview($get, $record))
                            ->columnSpanFull(),
                    ]),

                Section::make('Gambar')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('featured_image')
                            ->label('Gambar Utama')
                            ->disk(config('media.disk', 'public'))
                            ->visibility('public')
                            ->image()
                            ->acceptedFileTypes(config('media.accepted_mime_types'))
                            ->maxSize((int) config('media.featured.max_size', 5120))
                            ->previewable()
                            ->openable()
                            ->downloadable()
                            ->pasteable(false)
                            ->preventFilePathTampering()
                            ->rule(
                                Rule::dimensions()
                                    ->minWidth((int) config('media.featured.min_width', 1200))
                                    ->minHeight((int) config('media.featured.min_height', 675))
                            )
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): string {
                                try {
                                    return app(PostImageService::class)->storeFeaturedImage($file)['original'];
                                } catch (RuntimeException $exception) {
                                    throw ValidationException::withMessages([
                                        'featured_image' => $exception->getMessage(),
                                    ]);
                                }
                            })
                            ->helperText('JPG, PNG, atau WebP. Maksimal 5 MB. Resolusi minimal 1200 x 675 piksel. Rasio yang disarankan 16:9. Gunakan gambar berkualitas tinggi yang relevan dengan isi berita. Jika tidak mengunggah gambar, sistem akan menggunakan gambar default.'),

                        TextInput::make('featured_image_alt')
                            ->label('Teks Alternatif')
                            ->required(fn (Get $get): bool => filled($get('featured_image')))
                            ->maxLength(255)
                            ->helperText('Wajib jika gambar utama tersedia. Isi dengan deskripsi gambar.'),

                        TextInput::make('featured_image_caption')
                            ->label('Keterangan Gambar')
                            ->maxLength(500),

                        TextInput::make('featured_image_credit')
                            ->label('Kredit Foto')
                            ->maxLength(255),

                        FileUpload::make('detail_images')
                            ->label('Gambar Detail')
                            ->disk(config('media.disk', 'public'))
                            ->visibility('public')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->maxFiles(8)
                            ->acceptedFileTypes(config('media.accepted_mime_types'))
                            ->maxSize((int) config('media.featured.max_size', 5120))
                            ->previewable()
                            ->openable()
                            ->downloadable()
                            ->pasteable(false)
                            ->preventFilePathTampering()
                            ->rule(
                                Rule::dimensions()
                                    ->minWidth((int) config('media.featured.min_width', 1200))
                                    ->minHeight((int) config('media.featured.min_height', 675))
                            )
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): string {
                                try {
                                    return app(PostImageService::class)->storeFeaturedImage($file)['original'];
                                } catch (RuntimeException $exception) {
                                    throw ValidationException::withMessages([
                                        'detail_images' => $exception->getMessage(),
                                    ]);
                                }
                            })
                            ->helperText('Opsional. Jika kosong, halaman detail memakai gambar utama. Urutan gambar dapat diatur ulang.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function seoPreview(Get $get, ?Post $record): HtmlString
    {
        $post = $record ? $record->replicate() : new Post;
        $post->forceFill([
            'title' => $get('title') ?: $record?->title ?: 'Judul Berita',
            'slug' => $get('slug') ?: $record?->slug ?: 'judul-berita',
            'excerpt' => $get('excerpt') ?: $record?->excerpt,
            'content' => $get('content') ?: $record?->content,
            'seo_title' => $get('seo_title') ?: $record?->seo_title,
            'seo_description' => $get('seo_description') ?: $record?->seo_description,
            'canonical_url' => $get('canonical_url') ?: $record?->canonical_url,
            'og_image' => $get('og_image') ?: $record?->og_image,
            'featured_image' => $get('featured_image') ?: $record?->featured_image,
            'robots_index' => (bool) ($get('robots_index') ?? $record?->robots_index ?? true),
            'robots_follow' => (bool) ($get('robots_follow') ?? $record?->robots_follow ?? true),
            'status' => $record?->status ?? PostStatus::Draft,
        ]);

        try {
            $seo = app(SeoService::class)->forPost($post);
        } catch (\Throwable $exception) {
            return new HtmlString('<div class="text-sm text-danger-600">Preview belum tersedia: '.e($exception->getMessage()).'</div>');
        }

        $robots = ($seo->robotsIndex ? 'index' : 'noindex').', '.($seo->robotsFollow ? 'follow' : 'nofollow');
        $image = $seo->ogImage ?: '-';

        return new HtmlString(
            '<div class="space-y-1 text-sm">'.
            '<div><strong>'.e($seo->title).'</strong></div>'.
            '<div>'.e($seo->canonicalUrl).'</div>'.
            '<div>'.e($seo->description ?: '-').'</div>'.
            '<div>Robots: '.e($robots).'</div>'.
            '<div>OG Image: '.e($image).'</div>'.
            '</div>'
        );
    }
}
