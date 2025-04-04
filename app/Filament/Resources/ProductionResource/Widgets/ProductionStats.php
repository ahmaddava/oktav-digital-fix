<?php

namespace App\Filament\Resources\ProductionResource\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Production;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductionStats extends StatsOverviewWidget
{
    // Override polling interval method for Filament 3.3
    protected function getPollingInterval(): ?string
    {
        return '10s';
    }

    // Add method to listen for refresh events
    protected function setUp(): void
    {
        parent::setUp();
        
        // Add listener for refresh-stats event
        $this->listen('refresh-stats', function () {
            $this->refresh();
        });
    }

    protected function getStats(): array
    {
        // Ambil filter atau gunakan bulan berjalan
        $from = $this->filters['from'] ?? null;
        $until = $this->filters['until'] ?? null;

        // Tentukan periode bulan yang ditampilkan
        $currentPeriod = $this->getDatePeriod($from, $until);
        $previousPeriod = $this->getPreviousPeriod($currentPeriod['start']);

        // Query untuk periode saat ini dan sebelumnya (khusus clicks)
        $currentClickQuery = Production::whereBetween('created_at', [$currentPeriod['start'], $currentPeriod['end']]);
        $previousClickQuery = Production::whereBetween('created_at', [$previousPeriod['start'], $previousPeriod['end']]);

        // Current clicks
        $currentClicks = $this->getAggregatedResults($currentClickQuery);
        $previousClicks = $this->getAggregatedResults($previousClickQuery);

        // Ambil total clicks per mesin
        $mesin1Current = $currentClicks[Production::MESIN_1]['total_clicks'] ?? 0;
        $mesin2Current = $currentClicks[Production::MESIN_2]['total_clicks'] ?? 0;
        $mesin1Previous = $previousClicks[Production::MESIN_1]['total_clicks'] ?? 0;
        $mesin2Previous = $previousClicks[Production::MESIN_2]['total_clicks'] ?? 0;

        // Akumulasi counter dari seluruh data - Gunakan nilai counter yang di-input
        $counterResults = Production::selectRaw('machine_type, SUM(total_counter) as total_counter')
            ->groupBy('machine_type')
            ->get()
            ->keyBy('machine_type')
            ->mapWithKeys(fn ($item) => [
                $item['machine_type'] => $item['total_counter'],
            ])
            ->toArray();

        $mesin1Counter = $counterResults[Production::MESIN_1] ?? 0;
        $mesin2Counter = $counterResults[Production::MESIN_2] ?? 0;

        return [
            Stat::make('Clicks Mesin 1 - ' . $currentPeriod['label'], number_format($mesin1Current))
                ->description($this->getTrendDescription($mesin1Current, $mesin1Previous))
                ->color($this->getTrendColor($mesin1Current, $mesin1Previous))
                ->icon($this->getTrendIcon($mesin1Current, $mesin1Previous)),

            Stat::make('Clicks Mesin 2 - ' . $currentPeriod['label'], number_format($mesin2Current))
                ->description($this->getTrendDescription($mesin2Current, $mesin2Previous))
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
        return $query
            ->selectRaw('machine_type, SUM(total_clicks) as total_clicks')
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