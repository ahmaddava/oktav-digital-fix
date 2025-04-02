<?php

use Illuminate\Support\Facades\Route;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\InvoiceController;

Route::get('/', function () {
    return redirect()->to('/admin/login');
});
// Route::get('/invoices/print/{invoice}', function (Invoice $invoice) {
//     $invoice->load('invoiceProducts.product');
    
//     // Hitung subtotal
//     $subtotal = $invoice->invoiceProducts->sum(function($item) {
//         return $item->product->price * $item->quantity;
//     });
    
//     // Hitung total (jika ada pajak/diskon)
//     $total = $subtotal; // Jika belum ada kalkulasi tambahan
    
//     $pdf = Pdf::loadView('invoice-pdf', compact('invoice', 'subtotal', 'total'))
//               ->setPaper('a4');
    
//     return $pdf->stream("invoice-{$invoice->invoice_number}.pdf");
// })->name('invoices.print');
Route::get('/invoices/print/{invoice}', [InvoiceController::class, 'print'])
    ->name('invoices.print');
    Route::middleware([
        'auth:sanctum',
        config('jetstream.auth_session'),
        'verified',
        'can:manage_products' // Contoh
    ]);
    Route::middleware([
        'web',
        'auth:sanctum',
        'auth',
        'permission:invoice.create',
    ])->group(function () {
        // Rute yang dilindungi
    }); 