<?php

namespace App\Filament\Resources\ProductionResource\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Production;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionStats extends StatsOverviewWidget
{
    // Mengurangi polling interval untuk responsivitas lebih baik
    protected function getPollingInterval(): ?string
    {
        return '15s'; // 15 detik, lebih responsif dari 30s
    }

    // Add method to listen for refresh events
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mendengarkan event refresh-stats dan menghapus semua cache terkait
        $this->listen('refresh-stats', function () {
            // Hapus semua cache yang terkait dengan statistik produksi
            // Tambahkan logging untuk memastikan event diterima
            Log::info('Refresh stats event received, clearing caches');
            
            // Hapus cache berdasarkan pola untuk membersihkan semua cache terkait
            $this->clearProductionCaches();
            
            // Refresh widget
            $this->refresh();
        });
    }

    // Method untuk membersihkan semua cache terkait production stats
    private function clearProductionCaches(): void
    {
        // Hapus cache counter totals
        Cache::forget('production_counter_totals');
        
        // Hapus semua cache dengan awalan tertentu untuk current dan previous
        $keys = Cache::get('production_cache_keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        
        // Reset daftar key cache
        Cache::put('production_cache_keys', [], 3600);
        
        Log::info('Production caches cleared');
    }

    protected function getStats(): array
    {
        try {
            // Log awal proses getStats
            Log::info('Getting production stats');
            
            // Ambil filter atau gunakan bulan berjalan
            $from = $this->filters['from'] ?? null;
            $until = $this->filters['until'] ?? null;

            // Tentukan periode bulan yang ditampilkan
            $currentPeriod = $this->getDatePeriod($from, $until);
            $previousPeriod = $this->getPreviousPeriod($currentPeriod['start']);

            // Generate cache keys based on date periods
            $currentCacheKey = 'production_stats_current_' . $currentPeriod['start']->format('Y-m-d') . '_' . $currentPeriod['end']->format('Y-m-d');
            $previousCacheKey = 'production_stats_previous_' . $previousPeriod['start']->format('Y-m-d') . '_' . $previousPeriod['end']->format('Y-m-d');

            // Catat key cache untuk dapat dihapus nantinya
            $cacheKeys = Cache::get('production_cache_keys', []);
            if (!in_array($currentCacheKey, $cacheKeys)) {
                $cacheKeys[] = $currentCacheKey;
            }
            if (!in_array($previousCacheKey, $cacheKeys)) {
                $cacheKeys[] = $previousCacheKey;
            }
            if (!in_array('production_counter_totals', $cacheKeys)) {
                $cacheKeys[] = 'production_counter_totals';
            }
            Cache::put('production_cache_keys', $cacheKeys, 3600);

            // Dapatkan current clicks dengan caching yang lebih pendek (2 menit)
            $currentClicks = Cache::remember($currentCacheKey, 120, function () use ($currentPeriod) {
                Log::info('Cache miss for current period, querying database');
                $currentClickQuery = Production::whereBetween('created_at', [$currentPeriod['start'], $currentPeriod['end']]);
                return $this->getAggregatedResults($currentClickQuery);
            });

            // Dapatkan previous clicks dengan caching
            $previousClicks = Cache::remember($previousCacheKey, 120, function () use ($previousPeriod) {
                Log::info('Cache miss for previous period, querying database');
                $previousClickQuery = Production::whereBetween('created_at', [$previousPeriod['start'], $previousPeriod['end']]);
                return $this->getAggregatedResults($previousClickQuery);
            });

            // Konstanta untuk kode mesin (memastikan konsistensi dengan model)
            $MESIN_1 = 'mesin_1';
            $MESIN_2 = 'mesin_2';

            // Ambil total clicks per mesin
            $mesin1Current = $currentClicks[$MESIN_1]['total_clicks'] ?? 0;
            $mesin2Current = $currentClicks[$MESIN_2]['total_clicks'] ?? 0;
            $mesin1Previous = $previousClicks[$MESIN_1]['total_clicks'] ?? 0;
            $mesin2Previous = $previousClicks[$MESIN_2]['total_clicks'] ?? 0;

            // Get counter values (with caching) - waktu cache lebih pendek (2 menit)
            $counterResults = Cache::remember('production_counter_totals', 120, function () {
                Log::info('Cache miss for counter totals, querying database');
                
                // Gunakan DB facade untuk query counter lebih efisien
                // Dapatkan total counter dari produksi normal & penyesuaian
                return DB::table('productions')
                    ->select('machine_type')
                    ->selectRaw('SUM(total_counter) as total_counter')
                    ->groupBy('machine_type')
                    ->get()
                    ->keyBy('machine_type')
                    ->mapWithKeys(fn ($item) => [
                        $item->machine_type => $item->total_counter,
                    ])
                    ->toArray();
            });

            $mesin1Counter = $counterResults[$MESIN_1] ?? 0;
            $mesin2Counter = $counterResults[$MESIN_2] ?? 0;

            // Pre-compute descriptions for better performance
            $mesin1Description = $this->getTrendDescription($mesin1Current, $mesin1Previous);
            $mesin2Description = $this->getTrendDescription($mesin2Current, $mesin2Previous);

            // Log hasil untuk debugging
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
                    ->description('Akumulasi semua counter mesin 1')
                    ->color('warning')
                    ->icon('heroicon-m-cpu-chip'),

                Stat::make('Counter Mesin 2', number_format($mesin2Counter))
                    ->description('Akumulasi semua counter mesin 2')
                    ->color('warning')
                    ->icon('heroicon-m-cpu-chip'),
            ];
        } catch (\Exception $e) {
            // Log error untuk debugging
            Log::error('Error in ProductionStats::getStats: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            // Return empty stats jika terjadi error
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