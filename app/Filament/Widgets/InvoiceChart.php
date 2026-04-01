<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Invoice;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Carbon\Carbon;

class InvoiceChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Pendapatan vs Pengeluaran';
    protected static ?string $maxHeight = '300px';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        // Get filters from dashboard - with safe defaults  
        $filters = $this->filters ?? [];
        $selectedYear = $filters['year'] ?? now()->year;

        // Data pendapatan per bulan
        $incomeData = Trend::query(
            Invoice::query()->where('status', 'paid')
        )
            ->between(
                start: Carbon::createFromDate($selectedYear, 1, 1)->startOfYear(),
                end: Carbon::createFromDate($selectedYear, 12, 31)->endOfYear(),
            )
            ->perMonth()
            ->sum('grand_total');

        // Data pengeluaran per bulan
        $expenseData = Trend::model(Expense::class)
            ->dateColumn('expense_date')
            ->between(
                start: Carbon::createFromDate($selectedYear, 1, 1)->startOfYear(),
                end: Carbon::createFromDate($selectedYear, 12, 31)->endOfYear(),
            )
            ->perMonth()
            ->sum('amount');

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan',
                    'data' => $incomeData->map(fn (TrendValue $value) => $value->aggregate),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                    'borderColor' => '#22c55e',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => $expenseData->map(fn (TrendValue $value) => $value->aggregate),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.5)',
                    'borderColor' => '#ef4444',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}