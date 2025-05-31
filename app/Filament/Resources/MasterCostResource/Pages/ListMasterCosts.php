<?php

namespace App\Filament\Resources\MasterCostResource\Pages;

use App\Filament\Resources\MasterCostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMasterCosts extends ListRecords
{
    protected static string $resource = MasterCostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
