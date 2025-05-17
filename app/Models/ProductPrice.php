<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{
    use HasFactory;

    // Define the fillable fields for mass assignment
    protected $fillable = [
        'product_id',
        'min_quantity',
        'price',
    ];

    // Relationship to the Product model
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}