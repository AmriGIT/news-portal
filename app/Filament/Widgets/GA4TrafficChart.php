<?php

namespace App\Filament\Widgets;

use App\Services\GA4AnalyticsService;
use Filament\Widgets\ChartWidget;

class GA4TrafficChart extends ChartWidget
{
    protected static ?int $sort = -1;

    protected ?string $heading = 'Sumber Traffic GA4 (30 Hari)';

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $ga4Service = app(GA4AnalyticsService::class);
        $sources = $ga4Service->getTrafficSources(30);

        if (empty($sources)) {
            return [
                'datasets' => [
                    [
                        'label' => 'Pengunjung',
                        'data' => [0, 0, 0, 0],
                        'backgroundColor' => [
                            'rgba(99, 102, 241, 0.85)',
                            'rgba(34, 197, 94, 0.85)',
                            'rgba(245, 158, 11, 0.85)',
                            'rgba(107, 114, 128, 0.85)',
                        ],
                    ],
                ],
                'labels' => ['Google Search (Perlu GA4 API)', 'Direct', 'Social', 'Referral'],
            ];
        }

        $labels = array_keys($sources);
        $data = array_values($sources);

        $colors = [
            'rgba(34, 197, 94, 0.85)',    // Organic Search - green
            'rgba(99, 102, 241, 0.85)',   // Direct - indigo
            'rgba(236, 72, 153, 0.85)',   // Social - pink
            'rgba(245, 158, 11, 0.85)',   // Referral - amber
            'rgba(14, 165, 233, 0.85)',   // Unassigned / Sky
            'rgba(107, 114, 128, 0.85)',  // Other - gray
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Pengunjung Unik',
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderWidth' => 2,
                    'hoverOffset' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
