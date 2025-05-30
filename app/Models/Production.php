<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Production extends Model
{
    use HasFactory;

    const MESIN_1 = 'mesin_1';
    const MESIN_2 = 'mesin_2';
    
    protected $attributes = [
        'status' => 'pending', // Default status saat produksi dibuat
    ];
    
    protected $fillable = [
        'invoice_id',
        'machine_type',
        'status',
        'failed_prints',
        'total_clicks',
        'total_counter',
        'notes',
        'is_adjustment',
        'adjustment_value',
        'completed_at',
        'started_at', // New: Timestamp for when production starts
        'deadline', // New: Deadline for the production
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'is_adjustment' => 'boolean',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class)->withDefault();
    }

    protected static function booted()
    {
        // Before saving (new or update)
        static::saving(function ($production) {
            // Hitung total clicks dan counter
            $production->total_clicks = $production->calculateTotalClicks();
            
            // Jika tidak ada perubahan eksplisit pada total_counter, gunakan nilai default
            if (!$production->isDirty('total_counter')) {
                $production->total_counter = $production->calculateTotalCounter();
            }

            // Set completed_at jika status completed
            if ($production->status === 'completed' && empty($production->completed_at)) {
                $production->completed_at = now();
            }
        });

        // After creating a new record - reduce stock for failed prints
        static::created(function ($production) {
            // Reduce stock if there are failed prints
            if ($production->failed_prints > 0) {
                $production->reduceStockForFailedPrints($production->failed_prints);
            }
        });

        // After updating a record - check for changes in failed_prints
        static::updated(function ($production) {
            if ($production->wasChanged('failed_prints')) {
                $oldFailedPrints = $production->getOriginal('failed_prints') ?? 0;
                $newFailedPrints = $production->failed_prints;
                
                // If failed_prints increased, reduce additional stock
                if ($newFailedPrints > $oldFailedPrints) {
                    $additionalFailedPrints = $newFailedPrints - $oldFailedPrints;
                    $production->reduceStockForFailedPrints($additionalFailedPrints);
                }
            }
        });
    }
    
    // Hitung total clicks dengan failed prints yang ikut dihitung
    public function calculateTotalClicks()
    {
        $totalClicks = 0;
        
        if ($this->invoice && $this->invoice->exists && $this->invoice->products) {
            // Pertama hitung clicks untuk jumlah produk normal
            $totalClicks = $this->invoice->products
                ->where('type', Product::TYPE_DIGITAL_PRINT)
                ->sum(function ($product) {
                    $quantity = $product->pivot->quantity;
                    $clicks = $product->click ?? 0;
                    return $quantity * $clicks;
                });
            
            // Kemudian tambahkan clicks untuk failed prints
            // Failed prints dihitung per produk sesuai clicks produk
            if ($this->failed_prints > 0) {
                $failedPrintsClicks = $this->invoice->products
                    ->where('type', Product::TYPE_DIGITAL_PRINT)
                    ->sum(function ($product) {
                        $clicks = $product->click ?? 0;
                        return $this->failed_prints * $clicks;
                    });
                
                $totalClicks += $failedPrintsClicks;
            }
        }
        
        return $totalClicks;
    }

    // Hitung total counter (sama dengan total clicks untuk konsistensi)
    public function calculateTotalCounter()
    {
        return $this->calculateTotalClicks();
    }
    
    // Helper method to reduce stock for failed prints
    public function reduceStockForFailedPrints($failedPrintCount)
    {
        if ($failedPrintCount <= 0) {
            return;
        }
        
        // Get associated invoice and its products
        if ($this->invoice && $this->invoice->exists && $this->invoice->products) {
            foreach ($this->invoice->products as $product) {
                // Reduce stock by exactly the number of failed prints
                // NOT multiplied by quantity - just the direct number of failed prints
                $product->decrement('stock', $failedPrintCount);
            }
        }
    }
    
    // Helper method for the resource to get invoices available for production
    public static function getPendingProductions()
    {
        return self::where('status', 'pending')
            ->where(function ($query) {
                $query->where('is_adjustment', 0)
                    ->orWhereNull('is_adjustment');
            });
    }
}