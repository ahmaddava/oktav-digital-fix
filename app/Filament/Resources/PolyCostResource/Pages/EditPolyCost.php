<?php

namespace App\Filament\Resources\PolyCostResource\Pages;

use App\Filament\Resources\PolyCostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPolyCost extends EditRecord
{
    protected static string $resource = PolyCostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
