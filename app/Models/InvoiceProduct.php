<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class InvoiceProduct extends Pivot
{
    protected $table = 'invoice_product';
    protected $fillable = [
        'product_id',
        'quantity',
        'price', 
        'total_price',
        'sort',
    ]; 

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}