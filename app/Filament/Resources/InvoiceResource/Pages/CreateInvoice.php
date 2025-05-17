<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use Filament\Notifications\Notification;
use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    // Redirect ke tabel invoice
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    // Override method mutateFormDataBeforeCreate untuk perhitungan harga
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Menghitung grand_total berdasarkan produk yang dipilih
        $grandTotal = 0;
        
        if (isset($data['invoiceProducts']) && is_array($data['invoiceProducts'])) {
            foreach ($data['invoiceProducts'] as $product) {
                if (isset($product['product_id']) && isset($product['quantity'])) {
                    $productModel = Product::find($product['product_id']);
                    if ($productModel) {
                        $quantity = (int) $product['quantity'];
                        $price = $productModel->getPriceByQuantity($quantity);
                        $totalPrice = $price * $quantity;
                        $grandTotal += $totalPrice;
                    }
                }
            }
        }
        
        // Set grand_total
        $data['grand_total'] = $grandTotal;
        
        return $data;
    }

    // Handle simpan data + notifikasi
    protected function afterCreate(): void
    {
        // Sync produk dengan perhitungan harga
        $productsSync = [];
        
        foreach ($this->data['invoiceProducts'] as $item) {
            if (isset($item['product_id']) && isset($item['quantity'])) {
                $product = Product::find($item['product_id']);
                if ($product) {
                    $quantity = (int) $item['quantity'];
                    $price = $product->getPriceByQuantity($quantity);
                    $totalPrice = $price * $quantity;
                    
                    $productsSync[$item['product_id']] = [
                        'quantity' => $quantity,
                        'price' => $price,
                        'total_price' => $totalPrice
                    ];
                }
            }
        }
        
        DB::transaction(function () use ($productsSync) {
            // Sync produk dengan price dan total_price
            $this->record->products()->sync($productsSync);
            
            // Update grand_total di invoice
            $grandTotal = array_sum(array_column($productsSync, 'total_price'));
            $this->record->update(['grand_total' => $grandTotal]);
        });

        // Notifikasi
        Notification::make()
            ->title('Invoice created successfully')
            ->icon('heroicon-o-document-text')
            ->iconColor('success')
            ->send();
    }

    // Nonaktifkan notifikasi bawaan
    protected function getCreatedNotification(): ?Notification
    {
        return null; // Nonaktifkan notifikasi bawaan "Created"
    }
}