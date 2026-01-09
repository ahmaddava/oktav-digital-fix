<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ProductPrice;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_name',
        'price',
        'stock',
        'type', // Tambahkan ini
        'sku',
        'click',
    ];

    const TYPE_DIGITAL_PRINT = 'digital_print';
    const TYPE_JASA = 'jasa';

    protected static function booted()
    {
        static::creating(function (Product $product) {
            $prefix = match($product->type) {
                self::TYPE_DIGITAL_PRINT => 'DP',
                self::TYPE_JASA => 'JS',
                default => 'PRD',
            };

            if ($product->type === self::TYPE_JASA) {
                $product->stock = 0; // atau null
            }

            // Ambil nomor urut terakhir berdasarkan SKU
            $latestProduct = Product::where('type', $product->type)
                ->where('sku', 'like', $prefix . '-%')
                ->orderByRaw('CAST(SUBSTRING(sku, ' . (strlen($prefix) + 2) . ') AS UNSIGNED) DESC') // Extract number after prefix
                ->first();

            $nextNumber = 1;
            if ($latestProduct) {
                // Hapus prefix dan ambil angkanya
                $latestNumber = (int) substr($latestProduct->sku, strlen($prefix) + 1);
                $nextNumber = $latestNumber + 1;
            }
            
            // Format SKU: Prefix-001
            $product->sku = sprintf(
                '%s-%03d',
                $prefix,
                $nextNumber
            );
        });
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_product')
            ->withPivot('quantity');
    }
    
    public function invoiceProducts(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class, 'invoice_product')
            ->using(InvoiceProduct::class)
            ->withPivot(['quantity', 'sort']);
    }
    protected $casts = [
        'price' => 'integer',
    ];

    // Relasi ke ProductPrice
    public function prices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    // Define the method to get price based on quantity
    public function getPriceByQuantity($quantity)
    {
        // Fetch the price rule based on quantity
        $priceRule = $this->prices()->where('min_quantity', '<=', $quantity)
                                     ->orderByDesc('min_quantity') // Select the highest min_quantity that is <= quantity
                                     ->first();

        // Return the price if a rule is found, otherwise return the default price
        return $priceRule ? $priceRule->price : $this->price;
    }
}
