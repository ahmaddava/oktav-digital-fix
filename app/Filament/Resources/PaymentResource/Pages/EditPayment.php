<?php

// App/Filament/Resources/PaymentResource/Pages/EditPayment.php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use App\Models\Production;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected $oldApprovalStatus;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

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
        // Simpan status approval lama
        $this->oldApprovalStatus = $this->record->approval_status;
        
        // Gunakan nilai approval_status dari record jika tidak ada di data
        $approvalStatus = $data['approval_status'] ?? $this->record->approval_status;
        
        // Convert DP format
        if (isset($data['dp']) && is_string($data['dp'])) {
            $data['dp'] = (int) str_replace(['.', ','], '', $data['dp']);
        }

        // Handle payment status changes
        if (isset($data['status']) && $data['status'] !== $this->record->status) {
            $data['paid_at'] = ($data['status'] === 'paid') ? now() : null;
        }
        
        // Handle approval notes for unpaid approved invoices
        if ($approvalStatus === 'approved' && ($data['status'] ?? $this->record->status) === 'unpaid') {
            $data['approval_notes'] = 'Di-ACC sebelum pembayaran lunas';
        }
        
        // Set approval info
        if ($approvalStatus === 'approved') {
            $data['approved_by'] = \Illuminate\Support\Facades\Auth::id();
            $data['approved_at'] = now();
        }

        // Pastikan approval_status selalu diset
        $data['approval_status'] = $approvalStatus;

        return $data;
    }

    protected function afterSave(): void
    {
        $newStatus = $this->record->approval_status;
        $oldStatus = $this->oldApprovalStatus;
        
        // Tangani perubahan status secara manual
        if ($oldStatus !== $newStatus) {
            if ($newStatus === 'approved') {
                // Buat record production
                $this->record->production()->firstOrCreate(
                    ['invoice_id' => $this->record->id],
                    [
                        'production_date' => now(),
                        'status' => 'pending',
                        'payment_status' => $this->record->status,
                        'approval_notes' => $this->record->approval_notes
                    ]
                );
            } 
            elseif (($newStatus === 'pending' || $newStatus === 'rejected') && $oldStatus === 'approved') {
                // Hapus record production
                $this->record->production()->delete();
            }
        }
    }
}