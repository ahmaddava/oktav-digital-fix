<?php

namespace App\Filament\Resources\ProductionResource\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Production;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductionStats extends StatsOverviewWidget
{
    // Increase polling interval to reduce server load
    protected function getPollingInterval(): ?string
    {
        return '30s'; // Increased from 10s to 30s
    }

    // Add method to listen for refresh events
    protected function setUp(): void
    {
        parent::setUp();
        
        // Add listener for refresh-stats event
        $this->listen('refresh-stats', function () {
            // Clear relevant caches before refreshing
            Cache::forget('production_stats_current');
            Cache::forget('production_stats_previous');
            $this->refresh();
        });
    }

    protected function getStats(): array
    {
        // Get filters or use current month
        $from = $this->filters['from'] ?? null;
        $until = $this->filters['until'] ?? null;

        // Define the period shown
        $currentPeriod = $this->getDatePeriod($from, $until);
        $previousPeriod = $this->getPreviousPeriod($currentPeriod['start']);

        // Generate cache keys based on date periods
        $currentCacheKey = 'production_stats_current_' . $currentPeriod['start']->format('Y-m-d') . '_' . $currentPeriod['end']->format('Y-m-d');
        $previousCacheKey = 'production_stats_previous_' . $previousPeriod['start']->format('Y-m-d') . '_' . $previousPeriod['end']->format('Y-m-d');

        // Get current clicks (with caching)
        $currentClicks = Cache::remember($currentCacheKey, 300, function () use ($currentPeriod) {
            $currentClickQuery = Production::whereBetween('created_at', [$currentPeriod['start'], $currentPeriod['end']]);
            return $this->getAggregatedResults($currentClickQuery);
        });

        // Get previous clicks (with caching)
        $previousClicks = Cache::remember($previousCacheKey, 300, function () use ($previousPeriod) {
            $previousClickQuery = Production::whereBetween('created_at', [$previousPeriod['start'], $previousPeriod['end']]);
            return $this->getAggregatedResults($previousClickQuery);
        });

        // Get total clicks per machine
        $mesin1Current = $currentClicks[Production::MESIN_1]['total_clicks'] ?? 0;
        $mesin2Current = $currentClicks[Production::MESIN_2]['total_clicks'] ?? 0;
        $mesin1Previous = $previousClicks[Production::MESIN_1]['total_clicks'] ?? 0;
        $mesin2Previous = $previousClicks[Production::MESIN_2]['total_clicks'] ?? 0;

        // Get counter values (with caching)
        $counterResults = Cache::remember('production_counter_totals', 300, function () {
            return Production::select('machine_type')
                ->selectRaw('SUM(total_counter) as total_counter')
                ->groupBy('machine_type')
                ->get()
                ->keyBy('machine_type')
                ->mapWithKeys(fn ($item) => [
                    $item['machine_type'] => $item['total_counter'],
                ])
                ->toArray();
        });

        $mesin1Counter = $counterResults[Production::MESIN_1] ?? 0;
        $mesin2Counter = $counterResults[Production::MESIN_2] ?? 0;

        // Pre-compute descriptions for better performance
        $mesin1Description = $this->getTrendDescription($mesin1Current, $mesin1Previous);
        $mesin2Description = $this->getTrendDescription($mesin2Current, $mesin2Previous);

        return [
            Stat::make('Clicks Mesin 1 - ' . $currentPeriod['label'], number_format($mesin1Current))
                ->description($mesin1Description)
                ->color($this->getTrendColor($mesin1Current, $mesin1Previous))
                ->icon($this->getTrendIcon($mesin1Current, $mesin1Previous)),

            Stat::make('Clicks Mesin 2 - ' . $currentPeriod['label'], number_format($mesin2Current))
                ->description($mesin2Description)
                ->color($this->getTrendColor($mesin2Current, $mesin2Previous))
                ->icon($this->getTrendIcon($mesin2Current, $mesin2Previous)),

            Stat::make('Counter Mesin 1', number_format($mesin1Counter))
                ->description('Akumulasi semua counter mesin 1')
                ->color('warning')
                ->icon('heroicon-m-cpu-chip'),

            Stat::make('Counter Mesin 2', number_format($mesin2Counter))
                ->description('Akumulasi semua counter mesin 2')
                ->color('warning')
                ->icon('heroicon-m-cpu-chip'),
        ];
    }

    private function getAggregatedResults($query): array
    {
        // Use more efficient query with index hints
        return $query
            ->select('machine_type')
            ->selectRaw('SUM(total_clicks) as total_clicks')
            ->groupBy('machine_type')
            ->get()
            ->keyBy('machine_type')
            ->mapWithKeys(function ($item) {
                return [
                    $item['machine_type'] => [
                        'total_clicks' => $item['total_clicks'],
                    ],
                ];
            })
            ->toArray();
    }

    private function getDatePeriod($from, $until): array
    {
        $start = $from ? Carbon::parse($from) : now()->startOfMonth();
        $end = $until ? Carbon::parse($until) : now()->endOfMonth();

        return [
            'start' => $start,
            'end' => $end,
            'label' => $start->translatedFormat('F Y')
        ];
    }

    private function getPreviousPeriod(Carbon $currentStart): array
    {
        $previous = $currentStart->copy()->subMonth();
        return [
            'start' => $previous->startOfMonth(),
            'end' => $previous->endOfMonth()
        ];
    }

    private function getTrendDescription($current, $previous): string
    {
        $difference = $current - $previous;
        $formattedDiff = number_format(abs($difference));

        if ($previous === 0) {
            return "Peningkatan {$formattedDiff} (100%) dari bulan sebelumnya";
        }

        $percentage = round(abs($difference) / $previous * 100, 2);
        $trend = $difference >= 0 ? 'peningkatan' : 'penurunan';

        return "{$trend} {$formattedDiff} ({$percentage}%) dari bulan sebelumnya";
    }

    private function getTrendColor($current, $previous): string
    {
        return $current >= $previous ? 'success' : 'danger';
    }

    private function getTrendIcon($current, $previous): string
    {
        return $current >= $previous 
            ? 'heroicon-m-arrow-trending-up' 
            : 'heroicon-m-arrow-trending-down';
    }

    public static function canView(): bool
    {
        return true;
    }
}