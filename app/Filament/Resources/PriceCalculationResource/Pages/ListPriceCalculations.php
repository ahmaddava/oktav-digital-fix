<?php

namespace App\Filament\Resources\PriceCalculationResource\Pages;

use App\Filament\Resources\PriceCalculationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPriceCalculations extends ListRecords
{
    protected static string $resource = PriceCalculationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
