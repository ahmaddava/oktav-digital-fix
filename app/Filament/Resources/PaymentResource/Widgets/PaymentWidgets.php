<?php

namespace App\Filament\Resources\PaymentResource\Widgets;

use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class PaymentWidgets extends BaseWidget
{
    protected static ?string $pollingInterval = '60s'; // Gunakan properti statis untuk polling
    
    protected static $calculatedStats = null;
    protected static $statsFilters = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->listeners[] = 'filterChanged';
    }
    
    protected function getStats(): array
    {
        $currentFilters = $this->filters ?? [];
        
        if (self::$calculatedStats === null || self::$statsFilters !== $currentFilters) {
            $this->calculateAllStats($currentFilters);
        }
        
        $cashTotal = self::$calculatedStats['cash']['total'] ?? 0;
        $cashCount = self::$calculatedStats['cash']['count'] ?? 0;
        $transferTotal = self::$calculatedStats['transfer']['total'] ?? 0;
        $transferCount = self::$calculatedStats['transfer']['count'] ?? 0;
        $dpRemaining = self::$calculatedStats['dp_remaining'] ?? 0;
        $dpCount = self::$calculatedStats['dp_count'] ?? 0;

        return [
            Stat::make('Cash Payments', 'Rp ' . number_format($cashTotal, 0, ',', '.'))
                ->icon('heroicon-o-currency-dollar')
                ->color('warning')
                ->description("{$cashCount} invoices"),
            
            Stat::make('Transfer Payments', 'Rp ' . number_format($transferTotal, 0, ',', '.'))
                ->icon('heroicon-o-credit-card')
                ->color('info')
                ->description("{$transferCount} invoices"),
                
            Stat::make('Down Payment Remaining', 'Rp ' . number_format($dpRemaining, 0, ',', '.'))
                ->icon('heroicon-o-clock')
                ->color('danger')
                ->description("{$dpCount} unpaid invoices")
        ];
    }
    
    protected function calculateAllStats(array $filters): void
{
    self::$statsFilters = $filters;

    self::$calculatedStats = [
        'cash' => ['total' => 0, 'count' => 0],
        'transfer' => ['total' => 0, 'count' => 0],
        'dp_remaining' => 0,
        'dp_count' => 0
    ];

    // Hitung total uang masuk per metode pembayaran
    $totals = DB::table('invoices')
        ->select(
            'payment_method',
            DB::raw('COUNT(*) as count'),
            DB::raw("SUM(CASE WHEN status = 'paid' THEN grand_total ELSE dp END) as total_received")
        )
        ->whereIn('payment_method', ['cash', 'transfer'])
        ->when(isset($filters['month']), function ($query) use ($filters) {
            return $query->whereMonth('created_at', $filters['month']);
        })
        ->when(isset($filters['yearFilter']), function ($query) use ($filters) {
            return $query->whereYear('created_at', $filters['yearFilter']);
        })
        ->groupBy('payment_method')
        ->get();

    foreach ($totals as $row) {
        if (isset(self::$calculatedStats[$row->payment_method])) {
            self::$calculatedStats[$row->payment_method]['total'] = $row->total_received ?? 0;
            self::$calculatedStats[$row->payment_method]['count'] = $row->count ?? 0;
        }
    }

    // Hitung sisa tagihan (unpaid)
    $dpResult = DB::table('invoices')
        ->select(
            DB::raw('SUM(grand_total - dp) as total_remaining'),
            DB::raw('COUNT(*) as count')
        )
        ->where('status', 'unpaid')
        ->when(isset($filters['month']), function ($query) use ($filters) {
            return $query->whereMonth('created_at', $filters['month']);
        })
        ->when(isset($filters['yearFilter']), function ($query) use ($filters) {
            return $query->whereYear('created_at', $filters['yearFilter']);
        })
        ->first();

    self::$calculatedStats['dp_remaining'] = $dpResult->total_remaining ?? 0;
    self::$calculatedStats['dp_count'] = $dpResult->count ?? 0;
}

    
    public function refresh(): void
    {
        self::$calculatedStats = null;
        self::$statsFilters = null;
        parent::refresh();
    }
}