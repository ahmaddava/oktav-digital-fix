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
            // Gunakan 'item_type' sesuai dengan nama field di form (InvoiceResource)
            $type = $item['item_type'] ?? 'existing';

            // Jika tipenya existing, kita ambil model produknya untuk mendapatkan nama
            if ($type === 'existing') {
                $product = Product::find($item['product_id'] ?? null);
            }

            $this->record->invoiceProducts()->create([
                // Hanya set product_id jika tipenya 'existing'
                'product_id'   => $type === 'existing' ? ($item['product_id'] ?? null) : null,

                // Ambil nama dari produk (jika existing) atau dari inputan (jika custom)
                'product_name' => $type === 'existing' ? ($product?->product_name ?? 'Produk Tidak Ditemukan') : ($item['product_name'] ?? 'Produk Kustom'),

                'quantity'     => $item['quantity'] ?? 1,
                'price'        => $item['price'] ?? 0,
                'total_price'  => (($item['quantity'] ?? 1) * ($item['price'] ?? 0)),
                'file_path'    => $item['file_path'] ?? null,
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
