<?php

namespace App\Filament\Resources\ProductionItemResource\Pages;

use App\Filament\Resources\ProductionItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductionItems extends ListRecords
{
    protected static string $resource = ProductionItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
