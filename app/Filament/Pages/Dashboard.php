<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\InvoiceChart;
use App\Filament\Widgets\LowStockProducts;
use App\Filament\Widgets\StatsOverview;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $view = 'filament.pages.dashboard';

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
            'lg' => 3,
            'xl' => 3,
            '2xl' => 4,
        ];
    }

    protected function getViewData(): array
    {
        return [
            'welcomeMessage' => 'Selamat Datang di Panel Admin',
        ];
    }
}