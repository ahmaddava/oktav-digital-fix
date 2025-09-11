<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use NumberToWords\NumberToWords;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function print(Invoice $invoice)
    {
        // ✅ GANTI INI: Muat relasi 'invoiceProducts' yang berisi SEMUA item
        $invoice->load('invoiceProducts');

        // ✅ GANTI INI: Hitung subtotal dengan menjumlahkan kolom 'total_price' dari semua item
        $subtotal = $invoice->invoiceProducts->sum('total_price');

        // --- Bagian ini sudah benar, tidak perlu diubah ---

        // Konversi ke terbilang
        $numberToWords = new NumberToWords();
        $numberTransformer = $numberToWords->getNumberTransformer('id');
        $terbilang = $numberTransformer->toWords($subtotal);
        $terbilang = ucwords($terbilang) . ' Rupiah';

        // Kirim data yang benar ke view
        return view('invoice-pdf', [
            'invoice' => $invoice,
            'subtotal' => $subtotal,
            'terbilang' => $terbilang
        ]);
    }
}
