<?php

use Illuminate\Support\Facades\Route;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ReportExportController;


Route::get('/', function () {
    return redirect()->to('/admin/login');
});

//report export
Route::get('/export/financial-report', [ReportExportController::class, 'export'])
    ->middleware('auth')
    ->name('export.financial-report');

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
])->group(function () {});
