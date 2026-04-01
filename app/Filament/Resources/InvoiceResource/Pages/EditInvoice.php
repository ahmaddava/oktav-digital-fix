<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $invoiceProducts = $this->record->invoiceProducts()->get();
        $invoiceProductsData = [];

        foreach ($invoiceProducts as $item) {
            $invoiceProductsData[] = [
                'item_type' => $item->product_id ? 'existing' : 'custom',
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'total_price' => $item->total_price,
                'file_path' => $item->file_path,
            ];
        }

        $formData = array_merge(
            $this->record->toArray(),
            ['invoiceProducts' => $invoiceProductsData]
        );

        $this->form->fill($formData);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        $invoiceProducts = $this->form->getState()['invoiceProducts'] ?? [];

        $this->record->invoiceProducts()->delete();

        foreach ($invoiceProducts as $item) {
            $product = null;
            $type = $item['item_type'] ?? 'existing';
            
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

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Invoice updated successfully')
            ->icon('heroicon-o-document-text')
            ->iconColor('success')
            ->send();
    }
}
