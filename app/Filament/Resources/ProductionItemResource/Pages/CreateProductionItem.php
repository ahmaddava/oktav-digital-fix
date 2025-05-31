<?php

namespace App\Filament\Resources\ProductionItemResource\Pages;

use App\Filament\Resources\ProductionItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProductionItem extends CreateRecord
{
    protected static string $resource = ProductionItemResource::class;
}
