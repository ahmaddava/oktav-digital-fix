<?php

namespace App\Observers;

use App\Models\Invoice;

class InvoiceObserver
{
    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice): void
    {
        //
    }

    /**
     * Handle the Invoice "updated" event.
     */
    public function updated(Invoice $invoice): void
    {
        //
    }

    /**
     * Handle the Invoice "deleted" event.
     */
    public function deleted(Invoice $invoice): void
    {
        //
    }

    /**
     * Handle the Invoice "restored" event.
     */
    public function restored(Invoice $invoice): void
    {
        //
    }

    /**
     * Handle the Invoice "force deleted" event.
     */
    public function forceDeleted(Invoice $invoice): void
    {
        //
    }

    public function creating(Invoice $invoice) {
        $latest = Invoice::latest('sequence_number')->first();
        $invoice->sequence_number = $latest ? $latest->sequence_number + 1 : 1;
        
        if (empty($invoice->invoice_number)) {
            $datePart = $invoice->created_at ? clone $invoice->created_at : now()->timezone('Asia/Jakarta');
            $datePartFormat = $datePart->format('my');
            
            $pattern = 'INV-___' . $datePartFormat;
            
            $lastInvoiceThisMonth = Invoice::where('invoice_number', 'LIKE', $pattern)
                ->orderByDesc('invoice_number')
                ->first();

            if (!$lastInvoiceThisMonth) {
                $seq = 1;
            } else {
                $seq = ((int) substr($lastInvoiceThisMonth->invoice_number, 4, 3)) + 1;
            }

            $invoice->invoice_number = 'INV-' . str_pad($seq, 3, '0', STR_PAD_LEFT) . $datePartFormat;
        }
    }
}
