<?php

namespace App\Filament\Resources\PolyCostResource\Pages;

use App\Filament\Resources\PolyCostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPolyCosts extends ListRecords
{
    protected static string $resource = PolyCostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
