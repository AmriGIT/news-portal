<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Services\SiteSettingService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;
use UnitEnum;

class ManageSiteSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'SEO';

    protected static ?string $navigationLabel = 'Pengaturan Situs';

    protected static ?string $title = 'Pengaturan Situs';

    protected static ?int $navigationSort = 20;

    protected static string $routePath = '/site-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(app(SiteSettingService::class)->formData());
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', SiteSetting::class) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Identitas Situs')
                    ->columns(2)
                    ->schema([
                        TextInput::make('site_name')
                            ->label('Nama Situs')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('site_tagline')
                            ->label('Tagline')
                            ->maxLength(160),

                        Textarea::make('site_description')
                            ->label('Deskripsi Situs')
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull(),

                        FileUpload::make('site_logo')
                            ->label('Logo')
                            ->disk('public')
                            ->directory('site/branding')
                            ->visibility('public')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(2048)
                            ->openable()
                            ->downloadable(),

                        FileUpload::make('site_favicon')
                            ->label('Favicon')
                            ->disk('public')
                            ->directory('site/branding')
                            ->visibility('public')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(512)
                            ->openable()
                            ->downloadable(),
                    ]),

                Section::make('Kontak')
                    ->columns(2)
                    ->schema([
                        TextInput::make('contact_email')
                            ->label('Email Redaksi')
                            ->email()
                            ->maxLength(255),

                        TextInput::make('contact_phone')
                            ->label('Nomor Telepon')
                            ->maxLength(50),

                        Textarea::make('contact_address')
                            ->label('Alamat')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),

                Section::make('SEO Default')
                    ->columns(2)
                    ->schema([
                        TextInput::make('default_seo_title')
                            ->label('Default SEO Title')
                            ->maxLength(255)
                            ->helperText('Rekomendasi sekitar 60 karakter.'),

                        FileUpload::make('default_og_image')
                            ->label('Default Open Graph Image')
                            ->disk('public')
                            ->directory('site/seo')
                            ->visibility('public')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(3072)
                            ->openable()
                            ->downloadable(),

                        Textarea::make('default_seo_description')
                            ->label('Default SEO Description')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('Rekomendasi sekitar 160 karakter.')
                            ->columnSpanFull(),

                        Toggle::make('default_robots_index')
                            ->label('Default Robots Index')
                            ->default(true),

                        Toggle::make('default_robots_follow')
                            ->label('Default Robots Follow')
                            ->default(true),
                    ]),

                Section::make('Sosial Media')
                    ->columns(2)
                    ->schema([
                        TextInput::make('facebook_url')
                            ->label('Facebook URL')
                            ->url()
                            ->maxLength(255),

                        TextInput::make('instagram_url')
                            ->label('Instagram URL')
                            ->url()
                            ->maxLength(255),

                        TextInput::make('youtube_url')
                            ->label('YouTube URL')
                            ->url()
                            ->maxLength(255),

                        TextInput::make('x_url')
                            ->label('X URL')
                            ->url()
                            ->maxLength(255),

                        TextInput::make('tiktok_url')
                            ->label('TikTok URL')
                            ->url()
                            ->maxLength(255),
                    ]),

                Section::make('Footer')
                    ->schema([
                        Textarea::make('footer_text')
                            ->label('Footer Text')
                            ->rows(3)
                            ->maxLength(500),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Simpan Pengaturan')
                                ->submit('save'),
                        ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $oldAssets = SiteSetting::query()
            ->whereIn('key', ['site_logo', 'site_favicon', 'default_og_image'])
            ->pluck('value', 'key')
            ->all();

        $data = $this->form->getState();

        foreach (['site_logo', 'site_favicon', 'default_og_image'] as $assetKey) {
            $data[$assetKey] = $this->normalizeAssetState($data[$assetKey] ?? null, $oldAssets[$assetKey] ?? null);
        }

        app(SiteSettingService::class)->setMany($data);

        foreach ($oldAssets as $key => $oldPath) {
            $newPath = $data[$key] ?? null;

            if (filled($oldPath) && $oldPath !== $newPath) {
                $this->deleteAsset((string) $oldPath);
            }
        }

        Notification::make()
            ->success()
            ->title('Pengaturan situs berhasil disimpan.')
            ->send();
    }

    private function deleteAsset(string $path): void
    {
        if (preg_match('#^https?://#i', $path) === 1) {
            return;
        }

        try {
            Storage::disk('public')->delete($path);
        } catch (Throwable $exception) {
            Log::warning('Gagal menghapus asset site setting lama.', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function normalizeAssetState(mixed $state, ?string $currentPath = null): ?string
    {
        if (is_array($state)) {
            $files = array_values(array_filter($state));

            if (filled($currentPath)) {
                $replacement = collect($files)
                    ->map(fn (mixed $file): string => (string) $file)
                    ->first(fn (string $file): bool => $file !== $currentPath);

                if (filled($replacement)) {
                    return $replacement;
                }
            }

            return filled($files) ? (string) end($files) : null;
        }

        return filled($state) ? (string) $state : null;
    }
}
