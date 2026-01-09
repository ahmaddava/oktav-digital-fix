<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Get filters from dashboard - with safe defaults
        $filters = $this->filters ?? [];
        $selectedMonth = $filters['month'] ?? now()->month;
        $selectedYear = $filters['year'] ?? now()->year;

        // Query untuk income dari invoice yang sudah dibayar
        $totalIncome = Invoice::query()
            ->where('status', 'paid')
            ->whereYear('updated_at', $selectedYear)
            ->whereMonth('updated_at', $selectedMonth)
            ->sum('grand_total');

        // Query untuk expenses
        $totalExpense = Expense::query()
            ->whereYear('expense_date', $selectedYear)
            ->whereMonth('expense_date', $selectedMonth)
            ->sum('amount');

        // Query untuk piutang (unpaid invoices) - semua waktu
        $totalReceivables = Invoice::query()
            ->where('status', 'unpaid')
            ->sum('grand_total');

        // Query untuk invoice bulan ini
        $invoiceCount = Invoice::query()
            ->whereYear('created_at', $selectedYear)
            ->whereMonth('created_at', $selectedMonth)
            ->count();

        // Saldo
        $balance = $totalIncome - $totalExpense;
        $balanceColor = $balance >= 0 ? 'success' : 'danger';

        return [
            Stat::make('Invoice Bulan Ini', $invoiceCount)
                ->icon('heroicon-o-document-text')
                ->description('Total invoice dibuat')
                ->color('primary'),
                
            Stat::make('Pendapatan', 'Rp ' . number_format($totalIncome, 0, ',', '.'))
                ->icon('heroicon-o-arrow-trending-up')
                ->description('Invoice lunas bulan ini')
                ->color('success'),
                
            Stat::make('Pengeluaran', 'Rp ' . number_format($totalExpense, 0, ',', '.'))
                ->icon('heroicon-o-arrow-trending-down')
                ->description('Total pengeluaran')
                ->color('warning'),
                
            Stat::make('Saldo', 'Rp ' . number_format($balance, 0, ',', '.'))
                ->icon('heroicon-o-banknotes')
                ->description('Pendapatan - Pengeluaran')
                ->color($balanceColor),

            Stat::make('Piutang', 'Rp ' . number_format($totalReceivables, 0, ',', '.'))
                ->icon('heroicon-o-clock')
                ->description('Invoice belum lunas')
                ->color('danger'),
                
            Stat::make('Stok Rendah', Product::where('type', 'digital_print')->where('stock', '<', 100)->count())
                ->icon('heroicon-o-exclamation-triangle')
                ->description('Produk stok < 100')
                ->color('danger'),
        ];
    }
}