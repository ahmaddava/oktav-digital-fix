<?php

namespace App\Filament\Resources\ProductionCategoryResource\Pages;

use App\Filament\Resources\ProductionCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductionCategory extends EditRecord
{
    protected static string $resource = ProductionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
