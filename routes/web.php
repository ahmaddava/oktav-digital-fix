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
    'auth',
])->group(function () {
    Route::get('/artwork/view/{filename}', function ($filename) {
        $path = storage_path('app/public/artwork-invoices/' . $filename);
        
        if (!file_exists($path)) {
            abort(404, 'File artwork tidak ditemukan.');
        }
        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    })->name('artwork.view');

    Route::get('/language/{locale}', function ($locale) {
        if (in_array($locale, ['en', 'id'])) {
            session()->put('locale', $locale);
        }
        return redirect()->back();
    })->name('language.switch');
});
