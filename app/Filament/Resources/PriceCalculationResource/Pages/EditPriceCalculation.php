<?php

namespace App\Filament\Resources\PriceCalculationResource\Pages;

use App\Filament\Resources\PriceCalculationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPriceCalculation extends EditRecord
{
    protected static string $resource = PriceCalculationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
