<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\InvoiceProduct;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Invoice extends Model
{
    protected $fillable = [
        'sequence_number', 
        'status', 
        'approval_status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'name_customer',
        'customer_phone', 
        'notes', 
        'invoice_number',
        'dp',
        'payment_method',
        'grand_total',
        'customer_email',
        'alamat_customer',
        'attachment_path',
    ];


    public function scopeApprovedForProduction($query)
    {
        return $query->where('approval_status', 'approved');
    }

    protected $casts = [
        'created_at' => 'datetime',
        'paid_at' => 'datetime',
        'dp' => 'integer',
        'grand_total' => 'integer',
    ];
    
    // Variable untuk menyimpan produk yang telah di-load
    private $loadedProducts = null;

    /**
     * Get the products associated with the invoice
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'invoice_product')
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }
    
    /**
     * Get the invoice product items
     */
    public function invoiceProducts()
    {
        return $this->hasMany(InvoiceProduct::class);
    }

    /**
     * Get the production associated with this invoice
     */
    public function production()
    {
        return $this->hasOne(Production::class);
    }

    /**
     * Boot the model
     */
    protected static function boot() 
    {
        parent::boot();
    
        static::creating(function ($model) {
            // Generate sequence_number
            $latest = Invoice::select('sequence_number')
                ->latest('sequence_number')
                ->first();
            $model->sequence_number = $latest ? $latest->sequence_number + 1 : 1;
        });
    }

    /**
     * Scope for invoices available for production
     */
    public function scopeAvailableForProduction($query)
    {
        return $query->whereHas('products', function($query) {
            $query->where('type', Product::TYPE_DIGITAL_PRINT);
        })
        ->whereDoesntHave('production', function($query) {
            $query->where('status', 'completed');
        });
    }

    /**
     * Get the total price attribute
     */
    public function getTotalPriceAttribute()
    {
        return $this->products->sum(function ($product) {
            return $product->pivot->quantity * $product->pivot->price;
        });
    }

    /**
     * Get summarized product list for display (accessor)
     */
    public function getProductSummaryAttribute()
    {
        $products = $this->products;
        $limit = 2;
        
        $displayProducts = $products->take($limit)->map(function ($product) {
            return $product->product_name . ' (Qty: ' . $product->pivot->quantity . ')';
        })->implode(', ');

        if ($products->count() > $limit) {
            $remaining = $products->count() - $limit;
            $displayProducts .= ' +' . $remaining . ' more';
        }

        return $displayProducts;
    }

    /**
     * Get full product list for tooltips (accessor)
     */
    public function getProductFullListAttribute()
    {
        return $this->products->map(function ($product) {
            return $product->product_name . ' (Qty: ' . $product->pivot->quantity . ')';
        })->implode("\n");
    }

    /**
     * Get formatted grand total 
     */
    public function getFormattedGrandTotalAttribute()
    {
        return number_format((float)$this->grand_total, 0, ',', '.');
    }

    /**
     * Get display status text
     */
    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            'paid' => 'Paid',
            'unpaid' => 'Unpaid',
            default => 'Unknown',
        };
    }

    /**
     * Get status color for display
     */
    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'paid' => 'success',
            'unpaid' => 'danger',
            default => 'gray',
        };
    }

    public function getCalculatedGrandTotalAttribute()
    {
        return $this->products()->sum('total_price');
    }

    // Boot method untuk menghitung grand_total sebelum disimpan
    protected static function booted()
    {
        static::saving(function ($invoice) {
            // Jika invoice sudah ada (update), hitung ulang grand_total
            if ($invoice->exists) {
                $invoice->grand_total = $invoice->products()->sum('total_price');
            };

            
            static::updated(function ($invoice) {
                $newStatus = $invoice->approval_status;
                $oldStatus = $invoice->getOriginal('approval_status');
                
                if ($newStatus === 'approved') {
                    // Buat record production jika belum ada
                    $invoice->production()->firstOrCreate(
                        ['invoice_id' => $invoice->id],
                        [
                            'production_date' => now(),
                            'status' => 'pending',
                            'payment_status' => $invoice->status,
                            'notes' => $invoice->approval_notes
                        ]
                    );
                } 
                elseif (($newStatus === 'pending' || $newStatus === 'rejected') && $oldStatus === 'approved') {
                    // Hapus record production jika sebelumnya approved
                    $invoice->production()->delete();
                }
            });
        });
    }

    // Tambahkan accessor untuk remaining amount
    public function getRemainingAmountAttribute()
    {
        return $this->grand_total - ($this->dp ?? 0);
    }

    // Tambahkan scope untuk filter payment status
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    public function scopeWithDownPayment($query)
    {
        return $query->where('dp', '>', 0);
    }

    public function getAttachmentUrlAttribute()
    {
        return $this->attachment_path ? Storage::url($this->attachment_path) : null;
    }
}