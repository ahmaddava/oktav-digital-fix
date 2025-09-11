<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Product;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $invoiceProducts = $this->form->getState()['invoiceProducts'] ?? [];

        foreach ($invoiceProducts as $item) {
            $product = null;
            // Jika tipenya existing, kita ambil model produknya untuk mendapatkan nama
            if ($item['type'] === 'existing') {
                $product = Product::find($item['product_id']);
            }

            $this->record->invoiceProducts()->create([
                // FIX 1: Hanya set product_id jika tipenya 'existing'
                'product_id'   => $item['type'] === 'existing' ? $item['product_id'] : null,

                // FIX 2: Ambil nama dari produk (jika existing) atau dari inputan (jika custom)
                'product_name' => $item['type'] === 'existing' ? $product?->product_name : $item['product_name'],

                'quantity'     => $item['quantity'],
                'price'        => $item['price'],
                'total_price'  => ($item['quantity'] * $item['price']),
            ]);
        }
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Invoice created successfully')
            ->icon('heroicon-o-document-text')
            ->iconColor('success')
            ->send();
    }
}
