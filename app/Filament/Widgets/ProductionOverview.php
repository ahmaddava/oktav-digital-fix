<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Production;
use App\Models\InvoiceProduct;

class ProductionOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 3;
    protected static ?string $pollingInterval = '15s';
    protected int | string | array $columnSpan = 'full';
    protected int | string | array $columns = 3;

    protected function getStats(): array
    {
        $pendingCount = Production::where('status', 'pending')->count();
        $processingCount = Production::where('status', 'started')->count();
        $completedCount = Production::where('status', 'completed')->count();

        // Get count of individual items that are completed vs pending
        $totalItems = InvoiceProduct::count();
        $completedItems = InvoiceProduct::where('status', 'completed')->count();

        return [
            Stat::make(__('Produksi Selesai'), $completedCount)
                ->description(__('Total invoice yang telah selesai diproduksi'))
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 8])
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:scale-105 transition-transform',
                ]),

            Stat::make(__('Dalam Proses'), $processingCount)
                ->description(__('Invoice yang sedang dikerjakan di mesin'))
                ->descriptionIcon('heroicon-m-cog-8-tooth')
                ->color('info')
                ->chart([3, 5, 2, 6, 4, 8, 4, 6])
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:scale-105 transition-transform',
                ]),

            Stat::make(__('Antrian Produksi'), $pendingCount)
                ->description(__('Menunggu antrian mesin'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([2, 4, 6, 3, 5, 4, 7, 3])
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:scale-105 transition-transform',
                ]),
        ];
    }
}
