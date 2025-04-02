<?php

namespace App\Filament\Resources\ProductionResource\Pages;

use App\Models\Product;
use App\Filament\Resources\InvoiceResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ProductionResource;

class CreateProduction extends CreateRecord
{
    protected static string $resource = ProductionResource::class;

    protected function afterCreate(): void
    {
        // Kurangi stok produk setelah produksi selesai
        $production = $this->record;
        $invoice = $production->invoice;

        foreach ($invoice->products as $product) {
            if ($product->type === Product::TYPE_DIGITAL_PRINT) {
                $product->stock -= $product->pivot->quantity;
                $product->save();
            }
        }

        // Update waktu selesai produksi
        $production->update(['completed_at' => now()]);
    }

    protected function getRedirectUrl(): string
    {
        return InvoiceResource::getUrl('index');
    }

    protected function getCreateFormAction(): Action
    {
        return Action::make('create')
            ->label('Mulai Produksi')
            ->submit('create')
            ->color('primary');
    }
}