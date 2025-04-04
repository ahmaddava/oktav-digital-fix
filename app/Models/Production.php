<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Production extends Model
{
    const MESIN_1 = 'mesin_1';
    const MESIN_2 = 'mesin_2';
    
    // Hapus $with jika tidak diperlukan
    // protected $with = ['invoice'];
    
    protected $attributes = [
        'status' => 'pending', // Default status saat produksi dibuat
    ];
    
    protected $fillable = [
        'invoice_id', // Tetap ada di fillable meskipun tidak ada di database
        'machine_type',
        'completed_at',
        'status',
        'completed_at',
        'failed_prints',
        'total_clicks',
        'total_counter',
        'notes'
    ];

    public function invoice(): BelongsTo
    {
        // Jika kolom invoice_id tidak ada di tabel, gunakan relasi alternatif
        // Misal, dengan invoice_number atau relasi lain
        
        // Atau gunakan null untuk sementara
        return $this->belongsTo(Invoice::class)->withDefault();
    }

    protected static function booted()
    {
        static::saving(function ($production) {
            // Hitung total clicks (tanpa bergantung pada invoice_id)
            $production->total_clicks = $production->calculateTotalClicks();
            
            // Jika tidak ada perubahan eksplisit pada total_counter, gunakan nilai default
            if ($production->isDirty('total_counter')) {
                // Total counter sudah diubah secara manual, gunakan nilai tersebut
            } else {
                // Set nilai default untuk total_counter berdasarkan perhitungan
                $production->total_counter = $production->calculateTotalCounter();
            }
        });
    }
    
    // Hitung total clicks tanpa bergantung pada invoice
    public function calculateTotalClicks()
    {
        // Jika tidak ada invoice relation, gunakan nilai default atau logika alternatif
        $totalClicks = 0;
        
        // Jika ada relasi invoice dan products
        if ($this->invoice && $this->invoice->exists && $this->invoice->products) {
            $totalClicks = $this->invoice->products
                ->where('type', Product::TYPE_DIGITAL_PRINT)
                ->sum(function ($product) {
                    $quantity = $product->pivot->quantity;
                    $clicks = $product->click ?? 0;
                    return ($quantity + $this->failed_prints) * $clicks;
                });
        }
        
        return $totalClicks;
    }

    // Hitung total counter tanpa bergantung pada invoice
    public function calculateTotalCounter()
    {
        // Jika tidak ada invoice relation, gunakan nilai default atau logika alternatif
        $totalCounter = 0;
        
        // Jika ada relasi invoice dan products
        if ($this->invoice && $this->invoice->exists && $this->invoice->products) {
            $totalCounter = $this->invoice->products
                ->where('type', Product::TYPE_DIGITAL_PRINT)
                ->sum(function ($product) {
                    $quantity = $product->pivot->quantity;
                    $clicks = $product->click ?? 0;
                    return $quantity * $clicks;
                });
        }
        
        return $totalCounter;
    }
}