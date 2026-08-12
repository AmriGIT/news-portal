<?php

namespace App\Filament\Widgets;

use App\Enums\PostStatus;
use App\Models\Post;
use Filament\Widgets\ChartWidget;

class PostStatusChart extends ChartWidget
{
    protected static ?int $sort = -1;

    protected ?string $heading = 'Artikel per Status';

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $statuses = collect(PostStatus::cases());

        $data = $statuses->map(fn (PostStatus $status) => Post::where('status', $status)->count())->toArray();

        $labels = $statuses->map(fn (PostStatus $status) => $status->label())->toArray();

        $colors = [
            'rgba(107, 114, 128, 0.85)', // Draft - gray
            'rgba(245, 158, 11, 0.85)',   // Review - amber
            'rgba(14, 165, 233, 0.85)',   // Scheduled - sky
            'rgba(34, 197, 94, 0.85)',    // Published - green
            'rgba(239, 68, 68, 0.85)',    // Archived - red
        ];

        $borderColors = [
            'rgb(107, 114, 128)',
            'rgb(245, 158, 11)',
            'rgb(14, 165, 233)',
            'rgb(34, 197, 94)',
            'rgb(239, 68, 68)',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Artikel',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $borderColors,
                    'borderWidth' => 2,
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
