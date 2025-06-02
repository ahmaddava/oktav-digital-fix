<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceCalculation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_name',
        'size',
        'selected_items',
        'total_material_cost',
        'production_cost',
        'poly_cost',
        'knife_cost',
        'panjang_atas',
        'lebar_atas',
        'tinggi_atas',
        'panjang_bawah',
        'lebar_bawah',
        'tinggi_bawah',
        'board_panjang_atas',
        'board_panjang_bawah',
        'board_lebar_bawah',
        'board_lebar_atas',
        'board',
        'profit',
        'total_price',
        'notes'
    ];

    protected $casts = [
        'selected_items' => 'array',
        'total_material_cost' => 'decimal:2',
        'production_cost' => 'decimal:2',
        'poly_cost' => 'decimal:2',
        'knife_cost' => 'decimal:2',
        'profit' => 'decimal:2',
        'total_price' => 'decimal:2'
    ];
}