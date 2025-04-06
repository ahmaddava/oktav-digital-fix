<?php

namespace App\Filament\Resources\InvoiceResource\Widgets;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class PaymentStatsWidget extends BaseWidget
{
    // Static variable to cache query results
    protected static $calculatedStats = null;
    protected static $statsFilters = null;
    
    // Reduce polling frequency to reduce server load
    protected function getPollingInterval(): ?string
    {
        return '60s'; // Poll every 60 seconds to reduce server load
    }
    
    // Reset the cached data when filters change
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->listen('filterChanged', function () {
            // Reset static cache when filters change
            self::$calculatedStats = null;
            self::$statsFilters = null;
        });
    }
    
    protected function getStats(): array
    {
        // Check if we need to recalculate stats (if filters changed)
        $currentFilters = $this->filters ?? [];
        if (self::$calculatedStats === null || self::$statsFilters !== $currentFilters) {
            $this->calculateAllStats($currentFilters);
        }
        
        // Extract values from the cached calculation
        $cashTotal = self::$calculatedStats['cash']['total'] ?? 0;
        $cashCount = self::$calculatedStats['cash']['count'] ?? 0;
        $transferTotal = self::$calculatedStats['transfer']['total'] ?? 0;
        $transferCount = self::$calculatedStats['transfer']['count'] ?? 0;
        $cashTrend = self::$calculatedStats['cash']['trend'] ?? [0, 0, 0, 0, 0];
        $transferTrend = self::$calculatedStats['transfer']['trend'] ?? [0, 0, 0, 0, 0];

        return [
            Stat::make('Cash Payments', 'Rp ' . number_format($cashTotal, 0, ',', '.'))
                ->icon('heroicon-o-currency-dollar')
                ->color('warning')
                ->description("{$cashCount} invoices")
                ->chart($cashTrend),
            
            Stat::make('Transfer Payments', 'Rp ' . number_format($transferTotal, 0, ',', '.'))
                ->icon('heroicon-o-credit-card')
                ->color('info')
                ->description("{$transferCount} invoices")
                ->chart($transferTrend),
                
            Stat::make('Total Revenue', 'Rp ' . number_format($cashTotal + $transferTotal, 0, ',', '.'))
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->description(($cashCount + $transferCount) . " invoices")
        ];
    }
    
    /**
     * Calculate all stats in a single method to reduce database queries
     */
    protected function calculateAllStats(array $filters): void
    {
        // Store the filters used for this calculation
        self::$statsFilters = $filters;
        
        // Prepare the result container
        self::$calculatedStats = [
            'cash' => ['total' => 0, 'count' => 0, 'trend' => [0, 0, 0, 0, 0]],
            'transfer' => ['total' => 0, 'count' => 0, 'trend' => [0, 0, 0, 0, 0]]
        ];
        
        // 1. Get totals for each payment method in a single query
        $totals = DB::table('invoices')
            ->select(
                'payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(grand_total) as total')
            )
            ->whereIn('payment_method', ['cash', 'transfer'])
            ->when(isset($filters['monthFilter']), function ($query) use ($filters) {
                return $query->whereMonth('created_at', $filters['monthFilter']);
            })
            ->when(isset($filters['yearFilter']), function ($query) use ($filters) {
                return $query->whereYear('created_at', $filters['yearFilter']);
            })
            ->groupBy('payment_method')
            ->get();
            
        // Process totals
        foreach ($totals as $row) {
            if (isset(self::$calculatedStats[$row->payment_method])) {
                self::$calculatedStats[$row->payment_method]['total'] = $row->total ?? 0;
                self::$calculatedStats[$row->payment_method]['count'] = $row->count ?? 0;
            }
        }
        
        // 2. Get monthly trend data for the last 5 months in a single query
        $months = [];
        for ($i = 4; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $months[] = [
                'month' => $month->month,
                'year' => $month->year,
                'index' => 4 - $i // Convert to array index (0-4)
            ];
        }
        
        // Build the query to get all trend data at once
        $trends = DB::table('invoices')
            ->select(
                'payment_method',
                DB::raw('MONTH(created_at) as month'),
                DB::raw('YEAR(created_at) as year'),
                DB::raw('SUM(grand_total) as total')
            )
            ->whereIn('payment_method', ['cash', 'transfer'])
            ->where(function ($query) use ($months) {
                foreach ($months as $monthData) {
                    $query->orWhere(function ($q) use ($monthData) {
                        $q->whereMonth('created_at', $monthData['month'])
                          ->whereYear('created_at', $monthData['year']);
                    });
                }
            })
            ->when(isset($filters['otherFilter']), function($query) use ($filters) {
                // Apply any other filters if needed
                return $query;
            })
            ->groupBy('payment_method', DB::raw('MONTH(created_at)'), DB::raw('YEAR(created_at)'))
            ->get();
            
        // Process trend data
        foreach ($trends as $row) {
            if (!isset(self::$calculatedStats[$row->payment_method])) {
                continue;
            }
            
            // Find the corresponding month index
            foreach ($months as $monthData) {
                if ($monthData['month'] == $row->month && $monthData['year'] == $row->year) {
                    $index = $monthData['index'];
                    
                    // Scale value for chart (1-100 scale)
                    $value = $row->total ? min(100, max(1, $row->total / 100000)) : 0;
                    self::$calculatedStats[$row->payment_method]['trend'][$index] = $value;
                    break;
                }
            }
        }
    }
    
    // React to page refresh events
    public function refresh(): void
    {
        // Reset static cache to ensure fresh data
        self::$calculatedStats = null;
        self::$statsFilters = null;
        
        parent::refresh();
    }
}