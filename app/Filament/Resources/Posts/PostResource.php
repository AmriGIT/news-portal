<?php

namespace App\Filament\Resources\Posts;

use App\Actions\Post\TransitionPostStatusAction;
use App\Enums\PostStatus;
use App\Exceptions\InvalidPostStatusTransitionException;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\Pages\ViewPost;
use App\Filament\Resources\Posts\Schemas\PostForm;
use App\Filament\Resources\Posts\Schemas\PostInfolist;
use App\Filament\Resources\Posts\Tables\PostsTable;
use App\Models\Post;
use App\Models\User;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Throwable;
use UnitEnum;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Konten';

    protected static ?string $navigationLabel = 'Berita';

    protected static ?string $modelLabel = 'Berita';

    protected static ?string $pluralModelLabel = 'Berita';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['author', 'editor', 'category'])
            ->withCount('tags');

        $user = auth()->user();

        if ($user instanceof User && $user->isEditor()) {
            $query->where('author_id', $user->id);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return PostForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PostInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PostsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'view' => ViewPost::route('/{record}'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery()
            ->with(['author', 'editor', 'category', 'tags']);

        $user = auth()->user();

        if ($user instanceof User && $user->isAdmin()) {
            return $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        }

        if ($user instanceof User && $user->isEditor()) {
            $query->where('author_id', $user->id);
        }

        return $query;
    }

    /**
     * @return array<Action>
     */
    public static function getWorkflowActions(): array
    {
        return [
            self::workflowAction(
                name: 'submit_for_review',
                label: 'Kirim untuk Review',
                icon: Heroicon::OutlinedPaperAirplane,
                color: 'info',
                targetStatus: PostStatus::Review,
                ability: 'submitForReview',
                successMessage: 'Berita berhasil dikirim untuk review.',
            ),

            self::workflowAction(
                name: 'return_to_draft',
                label: 'Kembalikan ke Draf',
                icon: Heroicon::OutlinedArrowUturnLeft,
                color: 'gray',
                targetStatus: PostStatus::Draft,
                ability: 'returnToDraft',
                successMessage: 'Berita berhasil dikembalikan ke draf.',
            ),

            self::workflowAction(
                name: 'schedule',
                label: 'Jadwalkan',
                icon: Heroicon::OutlinedCalendar,
                color: 'warning',
                targetStatus: PostStatus::Scheduled,
                ability: 'schedule',
                successMessage: 'Berita berhasil dijadwalkan.',
                schema: [
                    DateTimePicker::make('published_at')
                        ->label('Waktu Publikasi')
                        ->timezone(config('app.timezone', 'Asia/Jakarta'))
                        ->seconds(false)
                        ->native(false)
                        ->required()
                        ->after(now())
                        ->helperText('Gunakan zona waktu aplikasi: Asia/Jakarta.'),
                ],
            ),

            self::workflowAction(
                name: 'publish',
                label: 'Terbitkan Sekarang',
                icon: Heroicon::OutlinedMegaphone,
                color: 'success',
                targetStatus: PostStatus::Published,
                ability: 'publish',
                successMessage: 'Berita berhasil diterbitkan.',
            ),

            self::workflowAction(
                name: 'archive',
                label: 'Arsipkan',
                icon: Heroicon::OutlinedArchiveBoxArrowDown,
                color: 'danger',
                targetStatus: PostStatus::Archived,
                ability: 'archive',
                successMessage: 'Berita berhasil diarsipkan.',
            ),
        ];
    }

    /**
     * @param  array<int, mixed>  $schema
     */
    private static function workflowAction(
        string $name,
        string $label,
        Heroicon $icon,
        string $color,
        PostStatus $targetStatus,
        string $ability,
        string $successMessage,
        array $schema = [],
    ): Action {
        return Action::make($name)
            ->label(fn (Post $record): string => self::labelFor($targetStatus, $record))
            ->icon($icon)
            ->color($color)
            ->authorize($ability)
            ->visible(fn (Post $record): bool => auth()->user()?->can($ability, $record) ?? false)
            ->requiresConfirmation($targetStatus !== PostStatus::Scheduled)
            ->modalHeading($label)
            ->modalSubmitActionLabel($label)
            ->schema($schema)
            ->successNotificationTitle($successMessage)
            ->failureNotificationTitle('Perubahan status berita gagal.')
            ->action(function (Post $record, array $data, Action $action): void {
                try {
                    $publishedAt = filled($data['published_at'] ?? null)
                        ? Carbon::parse($data['published_at'], config('app.timezone', 'Asia/Jakarta'))
                        : null;

                    app(TransitionPostStatusAction::class)->execute(
                        actor: auth()->user(),
                        post: $record,
                        targetStatus: $action->getName() === 'submit_for_review' ? PostStatus::Review : match ($action->getName()) {
                            'return_to_draft' => PostStatus::Draft,
                            'schedule' => PostStatus::Scheduled,
                            'publish' => PostStatus::Published,
                            'archive' => PostStatus::Archived,
                            default => throw InvalidPostStatusTransitionException::invalidTransition(),
                        },
                        publishedAt: $publishedAt,
                    );
                } catch (AuthorizationException) {
                    Notification::make()
                        ->danger()
                        ->title('Anda tidak memiliki izin untuk melakukan tindakan ini.')
                        ->send();

                    $action->failure();
                    $action->halt();
                } catch (InvalidPostStatusTransitionException $exception) {
                    Notification::make()
                        ->danger()
                        ->title($exception->getMessage())
                        ->send();

                    $action->failure();
                    $action->halt();
                } catch (Throwable) {
                    Notification::make()
                        ->danger()
                        ->title('Perubahan status berita gagal.')
                        ->send();

                    $action->failure();
                    $action->halt();
                }
            });
    }

    private static function labelFor(PostStatus $targetStatus, Post $record): string
    {
        if ($targetStatus === PostStatus::Draft && $record->status === PostStatus::Scheduled) {
            return 'Batalkan Jadwal';
        }

        if ($targetStatus === PostStatus::Draft && $record->status === PostStatus::Archived) {
            return 'Aktifkan Kembali sebagai Draf';
        }

        return match ($targetStatus) {
            PostStatus::Review => 'Kirim untuk Review',
            PostStatus::Draft => 'Kembalikan ke Draf',
            PostStatus::Scheduled => 'Jadwalkan',
            PostStatus::Published => 'Terbitkan Sekarang',
            PostStatus::Archived => 'Arsipkan',
        };
    }
}
