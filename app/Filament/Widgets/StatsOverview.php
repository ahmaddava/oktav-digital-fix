<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Produk', Product::count())
                ->icon('heroicon-o-archive-box')
                ->description('Jumlah total produk/jasa')
                ->color('success'),
                
            Stat::make('Total Invoice', Invoice::count())
                ->icon('heroicon-o-document-text')
                ->description('Total invoice yang pernah dibuat')
                ->color('primary'),
                
            Stat::make('Stok Rendah', Product::where('type', 'digital_print')
                    ->where('stock', '<', 100)
                    ->count())
                ->icon('heroicon-o-exclamation-triangle')
                ->description('Produk dengan stok di bawah 100')
                ->color('danger'),
        ];
    }
}