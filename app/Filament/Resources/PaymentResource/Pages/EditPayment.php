<?php

// App/Filament/Resources/PaymentResource/Pages/EditPayment.php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('View Details'),
                
            Actions\Action::make('view_invoice')
                ->label('View Invoice')
                ->icon('heroicon-o-document-text')
                ->url(fn () => InvoiceResource::getUrl('view', ['record' => $this->record]))
                ->color('info'),

            Actions\DeleteAction::make()
                ->visible(false), // Hide delete since we don't want to delete invoices from payment page
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Payment Updated')
            ->body('The payment information has been updated successfully.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Convert DP format if needed
        if (isset($data['dp']) && is_string($data['dp'])) {
            $data['dp'] = (int) str_replace(['.', ','], '', $data['dp']);
        }

        // Add timestamp for payment status changes
        if (isset($data['status']) && $data['status'] !== $this->record->status) {
            if ($data['status'] === 'paid') {
                $data['paid_at'] = now();
            } else {
                $data['paid_at'] = null;
            }
        }

        return $data;
    }
}