<?php

namespace App\Filament\Resources\ProductionResource\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Production;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class ProductionStats extends StatsOverviewWidget
{
    // Make stats refresh much more frequently
    protected function getPollingInterval(): ?string
    {
        return '5s'; // Poll every 5 seconds for faster updates
    }

    // Modern event handling using Livewire attributes
    #[On('refresh-stats')]
    public function handleRefreshEvent(): void
    {
        Log::info('ProductionStats received refresh-stats event at ' . now()->format('H:i:s.u'));
        $this->refresh();
    }

    protected function getStats(): array
    {
        try {
            // Log the start of the process with timestamp
            Log::info('Getting production stats at ' . now()->format('H:i:s.u'));
            
            // Get filters or use current month
            $from = $this->filters['from'] ?? null;
            $until = $this->filters['until'] ?? null;

            // Determine displayed periods
            $currentPeriod = $this->getDatePeriod($from, $until);
            $previousPeriod = $this->getPreviousPeriod($currentPeriod['start']);

            // IMPORTANT: Get data directly without caching for immediate updates
            Log::info('Querying database for current period stats');
            $currentClickQuery = Production::whereBetween('created_at', [$currentPeriod['start'], $currentPeriod['end']]);
            $currentClicks = $this->getAggregatedResults($currentClickQuery);

            Log::info('Querying database for previous period stats');
            $previousClickQuery = Production::whereBetween('created_at', [$previousPeriod['start'], $previousPeriod['end']]);
            $previousClicks = $this->getAggregatedResults($previousClickQuery);

            // Constants for machine codes (ensuring consistency with model)
            $MESIN_1 = 'mesin_1';
            $MESIN_2 = 'mesin_2';

            // Get clicks per machine
            $mesin1Current = $currentClicks[$MESIN_1]['total_clicks'] ?? 0;
            $mesin2Current = $currentClicks[$MESIN_2]['total_clicks'] ?? 0;
            $mesin1Previous = $previousClicks[$MESIN_1]['total_clicks'] ?? 0;
            $mesin2Previous = $previousClicks[$MESIN_2]['total_clicks'] ?? 0;

            // Get counter values - important change: no caching for immediate update
            Log::info('Getting counter totals directly from database');
            $counterResults = DB::table('productions')
                ->select('machine_type')
                ->selectRaw('SUM(total_counter) as total_counter')
                ->groupBy('machine_type')
                ->get()
                ->keyBy('machine_type')
                ->mapWithKeys(fn ($item) => [
                    $item->machine_type => $item->total_counter,
                ])
                ->toArray();

            $mesin1Counter = $counterResults[$MESIN_1] ?? 0;
            $mesin2Counter = $counterResults[$MESIN_2] ?? 0;

            // Pre-compute descriptions
            $mesin1Description = $this->getTrendDescription($mesin1Current, $mesin1Previous);
            $mesin2Description = $this->getTrendDescription($mesin2Current, $mesin2Previous);
            
            // Log the results for debugging
            Log::info("Stats calculated - M1: $mesin1Counter, M2: $mesin2Counter");

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
                    ->description("Akumulasi semua counter mesin 1")
                    ->color('warning')
                    ->icon('heroicon-m-cpu-chip'),

                Stat::make('Counter Mesin 2', number_format($mesin2Counter))
                    ->description("Akumulasi semua counter mesin 2")
                    ->color('warning')
                    ->icon('heroicon-m-cpu-chip'),
            ];
        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('Error in ProductionStats::getStats: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            // Return empty stats if error occurs
            return [
                Stat::make('Error', 'Terjadi kesalahan saat memuat data')
                    ->color('danger')
                    ->icon('heroicon-m-exclamation-triangle'),
            ];
        }
    }

    private function getAggregatedResults($query): array
    {
        // Use more efficient query with DB facade
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