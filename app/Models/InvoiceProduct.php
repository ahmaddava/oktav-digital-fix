<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class InvoiceProduct extends Pivot
{
    // Menggunakan class Pivot karena ini adalah model untuk tabel pivot
    protected $table = 'invoice_product';

    public $timestamps = false; // Biasanya tabel pivot tidak butuh timestamps

    protected $fillable = [
        'invoice_id',
        'product_id',
        'quantity',
        'price',
        'total_price',
        'sort',
        'product_name',
    ];

    /**
     * ✅ RELASI DARI PIVOT KE PRODUCT
     * Satu item pivot milik satu Product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
