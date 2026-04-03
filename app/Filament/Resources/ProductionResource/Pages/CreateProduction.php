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
        return $record;
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getState();
        $production = $this->record;
        $invoice = $production->invoice;

        if ($invoice && $invoice->exists) {
            // Update individual item statuses and machines from form
            foreach ($invoice->invoiceProducts as $item) {
                $statusKey = 'item_' . $item->id . '_status';
                $machineKey = 'item_' . $item->id . '_machine_id';

                if (isset($data[$statusKey])) {
                    $item->update([
                        'status' => $data[$statusKey],
                        'machine_id' => $data[$machineKey] ?? null,
                    ]);
                }
            }

            // Kurangi stok produk setelah produksi selesai
            foreach ($invoice->products as $product) {
                if ($product->type === Product::TYPE_DIGITAL_PRINT) {
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

}