<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use Filament\Notifications\Notification;
use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Product;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    // Redirect ke tabel invoice
    protected function getRedirectUrl(): string
    {
        // $invoice = $this->record;
        // $hasDigitalPrint = $invoice->products()->where('type', Product::TYPE_DIGITAL_PRINT)->exists();

        // if ($hasDigitalPrint) {
        //     return \App\Filament\Resources\ProductionResource::getUrl('create', [
        //         'invoice_id' => $invoice->id
        //     ]);
        // }

        return static::getResource()::getUrl('index');
    }

    // Handle simpan data + notifikasi
    protected function afterCreate(): void
    {
        // Sync produk
        $products = [];
        foreach ($this->data['invoiceProducts'] as $item) {
            $products[$item['product_id']] = ['quantity' => $item['quantity']];
        }
        $this->record->products()->sync($products);

        // Notifikasi
        Notification::make()
            ->title('Invoice created successfully')
            ->icon('heroicon-o-document-text')
            ->iconColor('success')
            ->send();
    }
    protected function getCreatedNotification(): ?Notification
    {
        return null; // Nonaktifkan notifikasi bawaan "Created"
    }

    
}