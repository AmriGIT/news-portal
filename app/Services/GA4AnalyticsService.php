<?php

namespace App\Services;

use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GA4AnalyticsService
{
    protected ?string $propertyId;
    protected ?string $rawCredentialsPath;

    public function __construct()
    {
        $this->propertyId = config('services.ga4.property_id');
        $this->rawCredentialsPath = config('services.ga4.credentials');
    }

    /**
     * Resolve absolute path to the credentials JSON file.
     */
    public function getCredentialsPath(): ?string
    {
        if (empty($this->rawCredentialsPath)) {
            return null;
        }

        if (file_exists($this->rawCredentialsPath)) {
            return realpath($this->rawCredentialsPath);
        }

        $basePath = base_path($this->rawCredentialsPath);
        if (file_exists($basePath)) {
            return realpath($basePath);
        }

        return null;
    }

    /**
     * Check if GA4 Data API is configured with valid credentials and property ID.
     */
    public function isConfigured(): bool
    {
        if (empty($this->propertyId)) {
            return false;
        }

        return $this->getCredentialsPath() !== null;
    }

    /**
     * Get Property ID formatted for GA4 Data API ("properties/XXXXX").
     */
    protected function getFormattedPropertyId(): string
    {
        $id = trim((string) $this->propertyId);

        if (str_starts_with($id, 'properties/')) {
            return $id;
        }

        return 'properties/' . $id;
    }

    /**
     * Fetch GA4 Visitor Overview (activeUsers, screenPageViews, sessions, bounceRate).
     *
     * @return array{
     *     configured: bool,
     *     active_users: int,
     *     pageviews: int,
     *     sessions: int,
     *     bounce_rate: float,
     *     message?: string
     * }
     */
    public function getVisitorOverview(int $days = 30): array
    {
        if (! $this->isConfigured()) {
            return [
                'configured' => false,
                'active_users' => 0,
                'pageviews' => 0,
                'sessions' => 0,
                'bounce_rate' => 0.0,
                'message' => 'GA4_PROPERTY_ID / ga4-credentials.json tidak ditemukan',
            ];
        }

        return Cache::remember("ga4_visitor_overview_{$days}", 300, function () use ($days) {
            try {
                $credentialsPath = $this->getCredentialsPath();

                $client = new BetaAnalyticsDataClient([
                    'credentials' => $credentialsPath,
                ]);

                $request = (new RunReportRequest())
                    ->setProperty($this->getFormattedPropertyId())
                    ->setDateRanges([
                        new DateRange([
                            'start_date' => "{$days}daysAgo",
                            'end_date' => 'today',
                        ]),
                    ])
                    ->setMetrics([
                        new Metric(['name' => 'activeUsers']),
                        new Metric(['name' => 'screenPageViews']),
                        new Metric(['name' => 'sessions']),
                        new Metric(['name' => 'bounceRate']),
                    ]);

                $response = $client->runReport($request);
                $rows = $response->getRows();

                if (count($rows) === 0) {
                    return [
                        'configured' => true,
                        'active_users' => 0,
                        'pageviews' => 0,
                        'sessions' => 0,
                        'bounce_rate' => 0.0,
                    ];
                }

                $metricValues = $rows[0]->getMetricValues();

                return [
                    'configured' => true,
                    'active_users' => (int) ($metricValues[0]->getValue() ?? 0),
                    'pageviews' => (int) ($metricValues[1]->getValue() ?? 0),
                    'sessions' => (int) ($metricValues[2]->getValue() ?? 0),
                    'bounce_rate' => round((float) ($metricValues[3]->getValue() ?? 0) * 100, 1),
                ];
            } catch (\Throwable $e) {
                Log::error('GA4 API Error: ' . $e->getMessage());

                // Shorten common error messages for clear dashboard display
                $msg = $e->getMessage();
                if (str_contains($msg, 'PERFORM_ACCESS') || str_contains($msg, 'permission')) {
                    $msg = 'Email Service Account belum diberi akses Viewer di GA4 Admin';
                } elseif (str_contains($msg, 'API has not been used') || str_contains($msg, 'disabled')) {
                    $msg = 'Google Analytics Data API belum diaktifkan di Google Cloud Console';
                }

                return [
                    'configured' => false,
                    'active_users' => 0,
                    'pageviews' => 0,
                    'sessions' => 0,
                    'bounce_rate' => 0.0,
                    'message' => $msg,
                ];
            }
        });
    }

    /**
     * Fetch Traffic Sources (Organic Search, Direct, Social, Referral).
     *
     * @return array<string, int>
     */
    public function getTrafficSources(int $days = 30): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        return Cache::remember("ga4_traffic_sources_{$days}", 300, function () use ($days) {
            try {
                $client = new BetaAnalyticsDataClient([
                    'credentials' => $this->getCredentialsPath(),
                ]);

                $request = (new RunReportRequest())
                    ->setProperty($this->getFormattedPropertyId())
                    ->setDateRanges([
                        new DateRange([
                            'start_date' => "{$days}daysAgo",
                            'end_date' => 'today',
                        ]),
                    ])
                    ->setDimensions([
                        new Dimension(['name' => 'sessionDefaultChannelGroup']),
                    ])
                    ->setMetrics([
                        new Metric(['name' => 'activeUsers']),
                    ]);

                $response = $client->runReport($request);
                $result = [];

                foreach ($response->getRows() as $row) {
                    $channel = $row->getDimensionValues()[0]->getValue() ?? 'Other';
                    $users = (int) ($row->getMetricValues()[0]->getValue() ?? 0);
                    $result[$channel] = $users;
                }

                return $result;
            } catch (\Throwable $e) {
                Log::error('GA4 Traffic Sources Error: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Fetch Device Breakdown (mobile, desktop, tablet).
     *
     * @return array<string, int>
     */
    public function getDeviceBreakdown(int $days = 30): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        return Cache::remember("ga4_device_breakdown_{$days}", 300, function () use ($days) {
            try {
                $client = new BetaAnalyticsDataClient([
                    'credentials' => $this->getCredentialsPath(),
                ]);

                $request = (new RunReportRequest())
                    ->setProperty($this->getFormattedPropertyId())
                    ->setDateRanges([
                        new DateRange([
                            'start_date' => "{$days}daysAgo",
                            'end_date' => 'today',
                        ]),
                    ])
                    ->setDimensions([
                        new Dimension(['name' => 'deviceCategory']),
                    ])
                    ->setMetrics([
                        new Metric(['name' => 'activeUsers']),
                    ]);

                $response = $client->runReport($request);
                $result = [];

                foreach ($response->getRows() as $row) {
                    $device = ucfirst($row->getDimensionValues()[0]->getValue() ?? 'Desktop');
                    $users = (int) ($row->getMetricValues()[0]->getValue() ?? 0);
                    $result[$device] = $users;
                }

                return $result;
            } catch (\Throwable $e) {
                Log::error('GA4 Device Breakdown Error: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Fetch Daily Visitors trend (last 30 days).
     *
     * @return array{labels: string[], users: int[], pageviews: int[]}
     */
    public function getDailyVisitorTrend(int $days = 30): array
    {
        if (! $this->isConfigured()) {
            return ['labels' => [], 'users' => [], 'pageviews' => []];
        }

        return Cache::remember("ga4_daily_trend_{$days}", 300, function () use ($days) {
            try {
                $client = new BetaAnalyticsDataClient([
                    'credentials' => $this->getCredentialsPath(),
                ]);

                $request = (new RunReportRequest())
                    ->setProperty($this->getFormattedPropertyId())
                    ->setDateRanges([
                        new DateRange([
                            'start_date' => "{$days}daysAgo",
                            'end_date' => 'today',
                        ]),
                    ])
                    ->setDimensions([
                        new Dimension(['name' => 'date']),
                    ])
                    ->setMetrics([
                        new Metric(['name' => 'activeUsers']),
                        new Metric(['name' => 'screenPageViews']),
                    ]);

                $response = $client->runReport($request);
                $labels = [];
                $users = [];
                $pageviews = [];

                foreach ($response->getRows() as $row) {
                    $rawDate = $row->getDimensionValues()[0]->getValue();
                    $labels[] = \Illuminate\Support\Carbon::createFromFormat('Ymd', $rawDate)->format('d M');
                    $users[] = (int) ($row->getMetricValues()[0]->getValue() ?? 0);
                    $pageviews[] = (int) ($row->getMetricValues()[1]->getValue() ?? 0);
                }

                return [
                    'labels' => $labels,
                    'users' => $users,
                    'pageviews' => $pageviews,
                ];
            } catch (\Throwable $e) {
                Log::error('GA4 Daily Trend Error: ' . $e->getMessage());
                return ['labels' => [], 'users' => [], 'pageviews' => []];
            }
        });
    }
}
