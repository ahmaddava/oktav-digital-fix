<?php

namespace App\Filament\Resources\ProductionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ProductionResource;
use App\Filament\Resources\ProductionResource\Widgets\ProductionFilterWidget;
use App\Filament\Resources\ProductionResource\Widgets\ProductionStatsWidget;
use Filament\Actions\CreateAction;

class ListProductions extends ListRecords
{
    protected static string $resource = ProductionResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ProductionResource\Widgets\ProductionStats::class,
        ];
    }
    
    protected function getActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Produksi Baru')
                ->icon('heroicon-o-plus'),
        ];
    }
}