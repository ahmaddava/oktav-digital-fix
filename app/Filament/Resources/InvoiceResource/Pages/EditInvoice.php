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
                'type' => $item->product_id ? 'existing' : 'custom',
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'total_price' => $item->total_price,
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

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Invoice updated successfully')
            ->icon('heroicon-o-document-text')
            ->iconColor('success')
            ->send();
    }
}
