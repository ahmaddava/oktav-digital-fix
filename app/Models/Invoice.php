<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\InvoiceProduct;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Invoice extends Model
{
    protected $fillable = [
        'sequence_number', 
        'status', 
        'name_customer',
        'customer_phone', 
        'notes', 
        'invoice_number',
        'dp',
        'payment_method',
        'grand_total',
        'customer_email',
        'alamat_customer',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'grand_total' => 'decimal:2',
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
            }
        });
    }
}