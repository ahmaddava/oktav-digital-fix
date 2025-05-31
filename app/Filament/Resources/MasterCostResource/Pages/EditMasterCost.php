<?php

namespace App\Filament\Resources\MasterCostResource\Pages;

use App\Filament\Resources\MasterCostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMasterCost extends EditRecord
{
    protected static string $resource = MasterCostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
