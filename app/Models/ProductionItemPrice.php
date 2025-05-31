<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionItemPrice extends Model
{
    protected $fillable = [
        'production_item_id', 'min_quantity', 'price'
    ];

    public function productionItem()
    {
        return $this->belongsTo(ProductionItem::class);
    }
}
