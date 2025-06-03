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
        'box_type_selection', // Jenis Box Utama
        'include_knife_cost',
        'dim_board_atas_p',
        'dim_board_atas_l',
        'dim_board_bawah_p',
        'dim_board_bawah_l',
        'dim_board_kuping_p',
        'dim_board_kuping_l',
        'dim_board_lidah_p',
        'dim_board_lidah_l',
        'dim_board_selongsong_p',
        'dim_board_selongsong_l',
        'dim_cl_atas_p',
        'dim_cl_atas_l',
        'dim_cl_bawah_p',
        'dim_cl_bawah_l',
        'dim_cl_kuping_p',
        'dim_cl_kuping_l',
        'dim_cl_lidah_p',
        'dim_cl_lidah_l',
        'dim_cl_selongsong_p',
        'dim_cl_selongsong_l',
        'dim_cd_atas_p',
        'dim_cd_atas_l',
        'dim_cd_bawah_p',
        'dim_cd_bawah_l',
        'dim_cd_lidah_p',
        'dim_cd_lidah_l',
        'dim_cd_selongsong_p',
        'dim_cd_selongsong_l',
        'dim_busa_p',
        'dim_busa_l',
        'final_qty_board_atas',
        'final_qty_board_bawah',
        'final_qty_board_kuping',
        'final_qty_board_lidah',
        'final_qty_board_selongsong',
        'final_qty_cl_atas',
        'final_qty_cl_bawah',
        'final_qty_cl_kuping',
        'final_qty_cl_lidah',
        'final_qty_cl_selongsong',
        'final_qty_cd_atas',
        'final_qty_cd_bawah',
        'final_qty_cd_lidah',
        'final_qty_cd_selongsong',
        'final_qty_busa',
        'total_board_cost',
        'total_cl_cost',
        'total_cd_cost',
        'total_busa_cost',
        'total_material_cost',
        'production_cost',
        'poly_cost',
        'knife_cost',
        'selected_items_ids', // JSON untuk menyimpan ID item yang dipilih
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