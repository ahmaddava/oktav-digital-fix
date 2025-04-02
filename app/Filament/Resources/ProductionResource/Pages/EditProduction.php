<?php

namespace App\Filament\Resources\ProductionResource\Pages;

use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ProductionResource;

class EditProduction extends EditRecord
{
    protected static string $resource = ProductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}