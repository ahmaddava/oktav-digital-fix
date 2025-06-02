<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'size',
        'dimension',
        'lebar_kertas',
        'panjang_kertas',
        'price',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'secondary_price' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductionCategory::class, 'category_id');
    }

    // Helper method untuk mendapatkan display name
    public function getDisplayNameAttribute(): string
    {
        $display = $this->name;
        
        if ($this->size) {
            $display .= " ({$this->size})";
        }
        
        if ($this->dimension) {
            $display .= " ({$this->dimension})";
        }
        
        if ($this->qty) {
            $display .= " (Qty: {$this->qty})";
        }
        
        return $display;
    }

    // Helper method untuk mendapatkan harga yang akan digunakan
    public function getEffectivePrice(): float
    {
        return $this->secondary_price ?? $this->price;
    }

    public function prices()
    {
        return $this->hasMany(ProductionItemPrice::class);
    }
}