<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Services\GA4AnalyticsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class GA4StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -3;

    protected ?string $pollingInterval = '30s';

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $ga4Service = app(GA4AnalyticsService::class);
        $measurementId = config('services.ga4.measurement_id', 'Belum dikonfigurasi');
        $ga4Data = $ga4Service->getVisitorOverview(30);

        $totalPosts = Post::count();
        $publishedPosts = Post::published()->count();
        $draftPosts = Post::draft()->count();

        // GA4 Visitor Card logic
        if ($ga4Data['configured']) {
            $visitorStat = Stat::make('Pengunjung GA4 (30 Hari)', number_format($ga4Data['active_users']))
                ->description(number_format($ga4Data['pageviews']) . ' Pageviews | Bounce ' . $ga4Data['bounce_rate'] . '%')
                ->descriptionIcon('heroicon-m-eye')
                ->color('success');
        } else {
            $visitorStat = Stat::make('GA4 Tracking', $measurementId)
                ->description($ga4Data['message'] ?? 'Script gtag.js aktif di publik')
                ->descriptionIcon('heroicon-m-signal')
                ->color('success');
        }

        // Post trend calculation
        $postsThisMonth = Post::where('created_at', '>=', Carbon::now()->startOfMonth())->count();
        $postsLastMonth = Post::whereBetween('created_at', [
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth(),
        ])->count();

        $postsTrend = $postsLastMonth > 0
            ? round((($postsThisMonth - $postsLastMonth) / $postsLastMonth) * 100, 1)
            : ($postsThisMonth > 0 ? 100 : 0);
        $postsTrendDesc = $postsTrend >= 0 ? "{$postsTrend}% naik dari bulan lalu" : abs($postsTrend) . '% turun dari bulan lalu';
        $postsTrendColor = $postsTrend >= 0 ? 'success' : 'danger';
        $postsTrendIcon = $postsTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';

        $weeklySparkline = collect(range(7, 0))->map(function ($i) {
            $start = Carbon::now()->subWeeks($i)->startOfWeek();
            $end = Carbon::now()->subWeeks($i)->endOfWeek();
            return Post::whereBetween('created_at', [$start, $end])->count();
        })->toArray();

        $totalCategories = Category::count();
        $totalTags = Tag::count();
        $totalAuthors = User::count();

        return [
            $visitorStat,

            Stat::make('Artikel Terbit', number_format($publishedPosts))
                ->description($postsTrendDesc)
                ->descriptionIcon($postsTrendIcon)
                ->color($postsTrendColor)
                ->chart($weeklySparkline),

            Stat::make('Draft / Review', number_format($draftPosts))
                ->description('Menunggu dipublikasikan')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('warning'),

            Stat::make('Artikel Bulan Ini', number_format($postsThisMonth))
                ->description("{$totalPosts} total artikel dipublikasikan")
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('Kategori & Tag', number_format($totalCategories) . ' Kategori')
                ->description("{$totalTags} tag aktif digunakan")
                ->descriptionIcon('heroicon-m-folder')
                ->color('primary'),

            Stat::make('Kontributor', number_format($totalAuthors))
                ->description('Total penulis / editor')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('gray'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}
