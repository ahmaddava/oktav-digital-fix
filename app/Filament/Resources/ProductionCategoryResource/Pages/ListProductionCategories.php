<?php

namespace App\Filament\Resources\ProductionCategoryResource\Pages;

use App\Filament\Resources\ProductionCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductionCategories extends ListRecords
{
    protected static string $resource = ProductionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
