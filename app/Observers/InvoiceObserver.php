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
        $latest = Invoice::latest()->first();
        $invoice->sequence_number = $latest ? $latest->sequence_number + 1 : 1;
        
        // Format: INV-{sequence_number}{tanggal}{bulan}{tahun}
        $invoice->invoice_number = 'INV-' . 
            str_pad($invoice->sequence_number, 3, '0', STR_PAD_LEFT) . 
            now()->format('dmy');
    }
}
