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
        $invoice->load('products');
        
        // Hitung total
        $subtotal = $invoice->products->sum(function($product) {
            return $product->pivot->quantity * $product->price;
        });

        // Konversi ke terbilang
        $numberToWords = new NumberToWords();

        // Pilih bahasa (Indonesian)
        $numberTransformer = $numberToWords->getNumberTransformer('id');

        // Konversi angka ke kata-kata
        $terbilang = $numberTransformer->toWords($subtotal);

        // Kapitalisasi huruf pertama dan tambahkan "Rupiah"
        $terbilang = ucwords($terbilang) . ' Rupiah';

        return view('invoice-pdf', [
            'invoice' => $invoice,
            'subtotal' => $subtotal,
            'terbilang' => $terbilang
        ]);
    }  //
    
}
