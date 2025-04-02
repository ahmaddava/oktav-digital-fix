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
        
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('quantity', 'price'); // Pastikan withPivot ada
    }
    
    public function invoiceProducts()
    {
        return $this->hasMany(InvoiceProduct::class); // HasMany untuk one-to-many
    }

    protected static function boot() {
        parent::boot();
    
        static::creating(function ($model) {
            // Generate sequence_number
            $latest = Invoice::latest()->first();
            $model->sequence_number = $latest ? $latest->sequence_number + 1 : 1;
        });
    }
    public function production()
    {
        return $this->hasOne(Production::class);
    }

    public function scopeAvailableForProduction($query)
    {
        return $query->whereHas('products', function($query) {
            $query->where('type', Product::TYPE_DIGITAL_PRINT);
        })
        ->whereDoesntHave('production', function($query) {
            $query->where('status', 'completed');
        });
    }
    public function getTotalPriceAttribute()
    {
        return $this->products->sum(function ($product) {
            return $product->pivot->quantity * $product->pivot->price;
        });
    }

    protected $casts = [
        'created_at' => 'datetime',
        'grand_total' => 'decimal:2',
    ];
}
