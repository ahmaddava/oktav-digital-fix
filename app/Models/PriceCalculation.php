<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceCalculation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_name',
        'box_type_selection',
        'quantity',
        'include_knife_cost',
        
        // Input Dimensi
        'atas_panjang',
        'atas_lebar',
        'atas_tinggi',
        'bawah_panjang',
        'bawah_lebar',
        'bawah_tinggi',

        // Item yang dipilih (JSON untuk fleksibilitas)
        'selected_items_ids', 

        // Dimensi Kertas Input
        'board_panjang_kertas',
        'board_lebar_kertas',
        'cover_luar_panjang_kertas',
        'cover_luar_lebar_kertas',
        'cover_dalam_panjang_kertas',
        'cover_dalam_lebar_kertas',
        'busa_panjang_kertas',
        'busa_lebar_kertas',

        // Dimensi Potongan Jadi - BOARD
        'panjang_board_atas',
        'lebar_board_atas',
        'panjang_board_bawah',
        'lebar_board_bawah',
        'panjang_board_kuping',
        'lebar_board_kuping',
        'panjang_board_lidah',
        'lebar_board_lidah',
        'panjang_board_selongsong',
        'lebar_board_selongsong',

        // Dimensi Potongan Jadi - COVER LUAR
        'panjang_cover_luar_atas',
        'lebar_cover_luar_atas',
        'panjang_cover_luar_bawah',
        'lebar_cover_luar_bawah',
        'panjang_cover_luar_kuping',
        'lebar_cover_luar_kuping',
        'panjang_cover_luar_lidah',
        'lebar_cover_luar_lidah',
        'panjang_cover_luar_selongsong',
        'lebar_cover_luar_selongsong',

        // Dimensi Potongan Jadi - COVER DALAM
        'panjang_cover_dalam_atas',
        'lebar_cover_dalam_atas',
        'panjang_cover_dalam_bawah',
        'lebar_cover_dalam_bawah',
        'panjang_cover_dalam_lidah',
        'lebar_cover_dalam_lidah',
        'panjang_cover_dalam_selongsong',
        'lebar_cover_dalam_selongsong',

        // Dimensi Potongan Jadi - BUSA
        'panjang_busa',
        'lebar_busa',

        // Kuantitas Final - BOARD
        'final_qty_board_atas',
        'final_qty_board_bawah',
        'final_qty_board_kuping',
        'final_qty_board_lidah',
        'final_qty_board_selongsong',

        // Kuantitas Final - COVER LUAR
        'final_qty_cover_luar_atas',
        'final_qty_cover_luar_bawah',
        'final_qty_cover_luar_kuping',
        'final_qty_cover_luar_lidah',
        'final_qty_cover_luar_selongsong',

        // Kuantitas Final - COVER DALAM
        'final_qty_cover_dalam_atas',
        'final_qty_cover_dalam_bawah',
        'final_qty_cover_dalam_lidah',
        'final_qty_cover_dalam_selongsong',
        
        // Kuantitas Final - BUSA
        'final_qty_busa',

        // Harga Satuan - BOARD
        'unit_price_board_atas',
        'unit_price_board_bawah',
        'unit_price_board_kuping',
        'unit_price_board_lidah',
        'unit_price_board_selongsong',

        // Harga Satuan - COVER LUAR
        'unit_price_cover_luar_atas',
        'unit_price_cover_luar_bawah',
        'unit_price_cover_luar_kuping',
        'unit_price_cover_luar_lidah',
        'unit_price_cover_luar_selongsong',

        // Harga Satuan - COVER DALAM
        'unit_price_cover_dalam_atas',
        'unit_price_cover_dalam_bawah',
        'unit_price_cover_dalam_lidah',
        'unit_price_cover_dalam_selongsong',

        // Harga Satuan - BUSA
        'unit_price_busa',

        // Biaya Lain dan Total
        'master_cost_size_selected',
        'master_cost_per_unit_value',
        'poly_dimension_selected',
        'poly_cost_value',
        // 'knife_cost_value', // Uncomment if you add this to the migration
        'total_price_estimate_numeric',
        'total_price_estimate_display',
        'notes',
        'created_at', // Add these if you want to explicitly fill them
        'updated_at', // Add these if you want to explicitly fill them
    ];

    protected $casts = [
        'selected_items_ids' => 'array', // Corrected to match the migration column name
        'master_cost_per_unit_value' => 'decimal:2',
        'poly_cost_value' => 'decimal:2',
        // 'knife_cost_value' => 'decimal:2', // Uncomment if you add this to the migration
        'total_price_estimate_numeric' => 'decimal:2',

        // Casts for unit prices
        'unit_price_board_atas' => 'decimal:2',
        'unit_price_board_bawah' => 'decimal:2',
        'unit_price_board_kuping' => 'decimal:2',
        'unit_price_board_lidah' => 'decimal:2',
        'unit_price_board_selongsong' => 'decimal:2',
        'unit_price_cover_luar_atas' => 'decimal:2',
        'unit_price_cover_luar_bawah' => 'decimal:2',
        'unit_price_cover_luar_kuping' => 'decimal:2',
        'unit_price_cover_luar_lidah' => 'decimal:2',
        'unit_price_cover_luar_selongsong' => 'decimal:2',
        'unit_price_cover_dalam_atas' => 'decimal:2',
        'unit_price_cover_dalam_bawah' => 'decimal:2',
        'unit_price_cover_dalam_lidah' => 'decimal:2',
        'unit_price_cover_dalam_selongsong' => 'decimal:2',
        'unit_price_busa' => 'decimal:2',
    ];
}
