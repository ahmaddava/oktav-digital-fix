<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Production extends Model
{
    const MESIN_1 = 'mesin_1';
    const MESIN_2 = 'mesin_2';
    protected $with = ['invoice'];
    protected $attributes = [
        'status' => 'pending', // Default status saat produksi dibuat
    ];
    protected $fillable = [
        'invoice_id',
        'machine_type', // Ganti dari machine_name
        'completed_at',
        'status',
        'completed_at',
        'failed_prints',
        'total_clicks',
        'notes'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class)->with('products');
    }

    protected static function booted()
    {
        static::saving(function ($production) {
            $production->total_clicks = $production->calculateTotalClicks();
        });
    }

    public function calculateTotalClicks()
    {
        return $this->invoice->products
            ->where('type', Product::TYPE_DIGITAL_PRINT)
            ->sum(function ($product) {
                $quantity = $product->pivot->quantity;
                $clicks = $product->click ?? 0;
                return ($quantity + $this->failed_prints) * $clicks;
            });
    }
}
