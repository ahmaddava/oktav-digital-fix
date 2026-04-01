<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\InvoiceChart;
use App\Filament\Widgets\LowStockProducts;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\LatestProduction;
use App\Filament\Widgets\ProductionOverview;
use App\Filament\Widgets\LatestInvoices;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?int $sort = 2;
    protected int | string | array $columns = 2;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('year')
                            ->label('Tahun')
                            ->options($this->getYearOptions())
                            ->default(now()->year),
                        Select::make('month')
                            ->label('Bulan')
                            ->options([
                                1 => 'Januari',
                                2 => 'Februari',
                                3 => 'Maret',
                                4 => 'April',
                                5 => 'Mei',
                                6 => 'Juni',
                                7 => 'Juli',
                                8 => 'Agustus',
                                9 => 'September',
                                10 => 'Oktober',
                                11 => 'November',
                                12 => 'Desember',
                            ])
                            ->default(now()->month),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getYearOptions(): array
    {
        $currentYear = now()->year;
        $years = [];
        for ($i = $currentYear - 5; $i <= $currentYear + 1; $i++) {
            $years[$i] = $i;
        }
        return $years;
    }

    public function getWidgets(): array
    {
        return [
            LatestProduction::class,
            LowStockProducts::class,
            StatsOverview::class,
            ProductionOverview::class,
            InvoiceChart::class,
            LatestInvoices::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            // LowStockProducts moved to main grid
        ];
    }

    public function getColumns(): int | array
    {
        return 2;
    }

    protected function getViewData(): array
    {
        return [
            'welcomeMessage' => 'Selamat Datang di Panel Admin',
        ];
    }
}