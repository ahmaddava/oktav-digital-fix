<?php

namespace App\Filament\Resources\ProductionResource\Pages;

use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ProductionResource;

class EditProduction extends EditRecord
{
    protected static string $resource = ProductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        if ($record && $record->invoice) {
            foreach ($record->invoice->invoiceProducts as $item) {
                $data['item_' . $item->id . '_status'] = $item->status;
                $data['item_' . $item->id . '_machine_id'] = $item->machine_id;
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->form->getState();
        $record = $this->getRecord();

        if ($record && $record->invoice) {
            foreach ($record->invoice->invoiceProducts as $item) {
                $statusKey = 'item_' . $item->id . '_status';
                $machineKey = 'item_' . $item->id . '_machine_id';

                if (isset($data[$statusKey])) {
                    $item->update([
                        'status' => $data[$statusKey],
                        'machine_id' => $data[$machineKey] ?? null,
                    ]);
                }
            }

            // Update global production status if all items are completed
            $totalItems = $record->invoice->invoiceProducts()->count();
            $completedItems = $record->invoice->invoiceProducts()->where('status', 'completed')->count();

            if ($totalItems > 0 && $totalItems === $completedItems) {
                $record->update([
                    'status' => 'completed',
                    'completed_at' => $record->completed_at ?? now(),
                ]);
            }
        }
    }
}