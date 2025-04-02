<?php

namespace App\Filament\Resources\ProductionResource\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Production;
use Carbon\Carbon;

class ProductionStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        // Ambil filter atau gunakan bulan berjalan
        $from = $this->filters['from'] ?? null;
        $until = $this->filters['until'] ?? null;
        
        // Tentukan periode bulan yang ditampilkan
        $currentPeriod = $this->getDatePeriod($from, $until);
        $previousPeriod = $this->getPreviousPeriod($currentPeriod['start']);
    
        // Query untuk periode saat ini dan sebelumnya
        $currentQuery = Production::whereBetween('created_at', [
            $currentPeriod['start'], 
            $currentPeriod['end']
        ]);
    
        $previousQuery = Production::whereBetween('created_at', [
            $previousPeriod['start'], 
            $previousPeriod['end']
        ]);
    
        // Hitung total untuk kedua periode dengan satu query
        $currentResults = $currentQuery->selectRaw('machine_type, SUM(total_clicks) as total_clicks')
            ->groupBy('machine_type')
            ->get()
            ->keyBy('machine_type')
            ->mapWithKeys(function ($item) {
                return [$item['machine_type'] => $item['total_clicks']];
            })
            ->toArray();
    
        $previousResults = $previousQuery->selectRaw('machine_type, SUM(total_clicks) as total_clicks')
            ->groupBy('machine_type')
            ->get()
            ->keyBy('machine_type')
            ->mapWithKeys(function ($item) {
                return [$item['machine_type'] => $item['total_clicks']];
            })
            ->toArray();
    
        // Atur nilai default jika tidak ada data
        $mesin1Current = $currentResults[Production::MESIN_1] ?? 0;
        $mesin2Current = $currentResults[Production::MESIN_2] ?? 0;
        
        $mesin1Previous = $previousResults[Production::MESIN_1] ?? 0;
        $mesin2Previous = $previousResults[Production::MESIN_2] ?? 0;
    
        return [
            Stat::make('Mesin 1 - ' . $currentPeriod['label'], number_format($mesin1Current))
                ->description($this->getTrendDescription($mesin1Current, $mesin1Previous))
                ->color($this->getTrendColor($mesin1Current, $mesin1Previous))
                ->icon($this->getTrendIcon($mesin1Current, $mesin1Previous)),
            
            Stat::make('Mesin 2 - ' . $currentPeriod['label'], number_format($mesin2Current))
                ->description($this->getTrendDescription($mesin2Current, $mesin2Previous))
                ->color($this->getTrendColor($mesin2Current, $mesin2Previous))
                ->icon($this->getTrendIcon($mesin2Current, $mesin2Previous)),
            
            Stat::make('Total Produksi', number_format($mesin1Current + $mesin2Current))
                ->description('Kinerja ' . $currentPeriod['label'])
                ->color('primary')
                ->icon('heroicon-o-chart-bar'),
        ];
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