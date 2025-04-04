<?php

namespace App\Filament\Resources\ProductionResource\Pages;

use App\Models\Product;
use App\Filament\Resources\InvoiceResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ProductionResource;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class CreateProduction extends CreateRecord
{
    protected static string $resource = ProductionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Handle completed_at date if status is completed
        if (isset($data['status']) && $data['status'] === 'completed') {
            $data['completed_at'] = now();
        }
        
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Create the production record
        $record = static::getModel()::create($data);
        
        // The stock reduction for normal products happens in afterCreate
        // Failed prints stock reduction happens in the model's boot method
        
        return $record;
    }

    protected function afterCreate(): void
    {
        // Kurangi stok produk setelah produksi selesai
        $production = $this->record;
        $invoice = $production->invoice;

        if ($invoice && $invoice->exists) {
            foreach ($invoice->products as $product) {
                if ($product->type === Product::TYPE_DIGITAL_PRINT) {
                    // We only reduce stock for the actual prints, not failed ones
                    // Failed prints are handled separately in the Production model
                    $product->stock -= $product->pivot->quantity;
                    $product->save();
                }
            }

            // Show success notification with invoice details
            Notification::make()
                ->title('Produksi dimulai')
                ->body('Produksi untuk Invoice ' . $invoice->invoice_number . ' telah dimulai')
                ->success()
                ->send();
        }

        // Update waktu selesai produksi if status is completed
        if ($production->status === 'completed' && empty($production->completed_at)) {
            $production->update(['completed_at' => now()]);
        }
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

    // Remove the getBreadcrumbs method completely - let Filament handle breadcrumbs
    // This method was causing issues with the array structure
}