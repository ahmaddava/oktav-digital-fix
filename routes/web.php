<?php

use Illuminate\Support\Facades\Route;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\InvoiceController;

Route::get('/', function () {
    return redirect()->to('/admin/login');
});

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
    }); 