<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use Filament\Widgets\ChartWidget;

class CategoryDistributionChart extends ChartWidget
{
    protected static ?int $sort = -1;

    protected ?string $heading = 'Distribusi Artikel per Kategori';

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $categories = Category::withCount('posts')
            ->orderByDesc('posts_count')
            ->limit(8)
            ->get();

        $colors = [
            'rgba(99, 102, 241, 0.85)',   // indigo
            'rgba(168, 85, 247, 0.85)',    // purple
            'rgba(236, 72, 153, 0.85)',    // pink
            'rgba(14, 165, 233, 0.85)',    // sky
            'rgba(34, 197, 94, 0.85)',     // green
            'rgba(245, 158, 11, 0.85)',    // amber
            'rgba(239, 68, 68, 0.85)',     // red
            'rgba(107, 114, 128, 0.85)',   // gray
        ];

        $borderColors = [
            'rgb(99, 102, 241)',
            'rgb(168, 85, 247)',
            'rgb(236, 72, 153)',
            'rgb(14, 165, 233)',
            'rgb(34, 197, 94)',
            'rgb(245, 158, 11)',
            'rgb(239, 68, 68)',
            'rgb(107, 114, 128)',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Artikel',
                    'data' => $categories->pluck('posts_count')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $categories->count()),
                    'borderColor' => array_slice($borderColors, 0, $categories->count()),
                    'borderWidth' => 2,
                    'hoverOffset' => 8,
                ],
            ],
            'labels' => $categories->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
