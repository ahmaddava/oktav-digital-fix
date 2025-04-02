<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\InvoiceChart;
use App\Filament\Widgets\LowStockProducts;
use App\Filament\Widgets\StatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $view = 'filament.pages.dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
            InvoiceChart::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            LowStockProducts::class,
        ];
    }

    public function getColumns(): int | array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'lg' => 2, // Mengubah menjadi 2 kolom untuk layout yang lebih seimbang
            'xl' => 3,
            '2xl' => 4, // Menambahkan kolom untuk layar lebar
        ];
    }

    // Custom HTML untuk tampilan dashboard
    protected function getViewData(): array
    {
        return [
            'welcomeMessage' => 'Selamat Datang di Panel Admin',
        ];
    }
}