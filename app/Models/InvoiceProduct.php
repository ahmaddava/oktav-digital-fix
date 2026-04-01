<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceProduct extends Model
{
    // Menggunakan class Model karena ini adalah model mandiri untuk tabel pivot
    protected $table = 'invoice_product';

    public $timestamps = true; // Tabel ini sekarang menggunakan timestamps

    protected $fillable = [
        'invoice_id',
        'product_id',
        'quantity',
        'price',
        'total_price',
        'sort',
        'product_name',
        'status',
        'keterangan',
        'item_type',
        'panjang',
        'lebar',
        'machine_id',
        'file_path',
    ];

    /**
     * ✅ RELASI DARI PIVOT KE INVOICE
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * ✅ RELASI DARI PIVOT KE PRODUCT
     * Satu item pivot milik satu Product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * ✅ RELASI DARI PIVOT KE MACHINE
     * Satu item pivot dikerjakan di satu Machine.
     */
    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}
