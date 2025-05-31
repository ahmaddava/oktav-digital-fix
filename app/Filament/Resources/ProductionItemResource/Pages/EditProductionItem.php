<?php

namespace App\Filament\Resources\ProductionItemResource\Pages;

use App\Filament\Resources\ProductionItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductionItem extends EditRecord
{
    protected static string $resource = ProductionItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
