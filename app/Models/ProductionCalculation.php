<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionCalculation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'production_calculations'; // Opsional jika nama tabel sudah sesuai konvensi

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_name',
        'box_type_selection',
        'size',
        'poly_dimension',
        'include_knife_cost',
        'quantity',

        // Dimensi Input User
        'atas_panjang',
        'atas_lebar',
        'atas_tinggi',
        'bawah_panjang',
        'bawah_lebar',
        'bawah_tinggi',

        // Pilihan Komponen & Item Terpilih
        'includeBoard',
        'selected_item_board',
        'includeCoverLuar',
        'selected_item_cover_luar',
        'includeCoverDalam',
        'selected_item_cover_dalam',
        'includeBusa',
        'selected_item_busa',

        // Hasil Perhitungan Dimensi Komponen (Potongan Jadi) & Kertas Bahan
        'panjang_board_atas', 'lebar_board_atas',
        'panjang_board_bawah', 'lebar_board_bawah',
        'panjang_board_kuping', 'lebar_board_kuping',
        'panjang_board_lidah', 'lebar_board_lidah',
        'panjang_board_selongsong', 'lebar_board_selongsong',
        'board_panjang_kertas', 'board_lebar_kertas',

        'panjang_cover_luar_atas', 'lebar_cover_luar_atas',
        'panjang_cover_luar_bawah', 'lebar_cover_luar_bawah',
        'panjang_cover_luar_kuping', 'lebar_cover_luar_kuping',
        'panjang_cover_luar_lidah', 'lebar_cover_luar_lidah',
        'panjang_cover_luar_selongsong', 'lebar_cover_luar_selongsong',
        'cover_luar_panjang_kertas', 'cover_luar_lebar_kertas',

        'panjang_cover_dalam_atas', 'lebar_cover_dalam_atas',
        'panjang_cover_dalam_bawah', 'lebar_cover_dalam_bawah',
        'panjang_cover_dalam_lidah', 'lebar_cover_dalam_lidah',
        'panjang_cover_dalam_selongsong', 'lebar_cover_dalam_selongsong',
        'cover_dalam_panjang_kertas', 'cover_dalam_lebar_kertas',

        'panjang_busa', 'lebar_busa',
        'busa_panjang_kertas', 'busa_lebar_kertas',

        // Hasil Perhitungan Kuantitas dari Bahan
        'qty1_board_atas', 'qty2_board_atas', 'final_qty_board_atas',
        'qty1_board_bawah', 'qty2_board_bawah', 'final_qty_board_bawah',
        'qty1_board_kuping', 'qty2_board_kuping', 'final_qty_board_kuping',
        'qty1_board_lidah', 'qty2_board_lidah', 'final_qty_board_lidah',
        'qty1_board_selongsong', 'qty2_board_selongsong', 'final_qty_board_selongsong',

        'qty1_cover_luar_atas', 'qty2_cover_luar_atas', 'final_qty_cover_luar_atas',
        'qty1_cover_luar_bawah', 'qty2_cover_luar_bawah', 'final_qty_cover_luar_bawah',
        'qty1_cover_luar_kuping', 'qty2_cover_luar_kuping', 'final_qty_cover_luar_kuping',
        'qty1_cover_luar_lidah', 'qty2_cover_luar_lidah', 'final_qty_cover_luar_lidah',
        'qty1_cover_luar_selongsong', 'qty2_cover_luar_selongsong', 'final_qty_cover_luar_selongsong',

        'qty1_cover_dalam_atas', 'qty2_cover_dalam_atas', 'final_qty_cover_dalam_atas',
        'qty1_cover_dalam_bawah', 'qty2_cover_dalam_bawah', 'final_qty_cover_dalam_bawah',
        'qty1_cover_dalam_lidah', 'qty2_cover_dalam_lidah', 'final_qty_cover_dalam_lidah',
        'qty1_cover_dalam_selongsong', 'qty2_cover_dalam_selongsong', 'final_qty_cover_dalam_selongsong',

        'qty1_busa', 'qty2_busa', 'final_qty_busa',

        // Hasil Perhitungan Harga Satuan Komponen (Rp/pcs)
        'unit_price_board_atas', 'unit_price_board_bawah', 'unit_price_board_kuping',
        'unit_price_board_lidah', 'unit_price_board_selongsong',

        'unit_price_cover_luar_atas', 'unit_price_cover_luar_bawah', 'unit_price_cover_luar_kuping',
        'unit_price_cover_luar_lidah', 'unit_price_cover_luar_selongsong',

        'unit_price_cover_dalam_atas', 'unit_price_cover_dalam_bawah', 'unit_price_cover_dalam_lidah',
        'unit_price_cover_dalam_selongsong',

        'unit_price_busa',

        // Estimasi Total Biaya
        'total_price_estimate_numeric',
        'total_price_estimate_display',

        // 'user_id', // Uncomment jika Anda menggunakan user_id
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',

        'atas_panjang' => 'decimal:4',
        'atas_lebar' => 'decimal:4',
        'atas_tinggi' => 'decimal:4',
        'bawah_panjang' => 'decimal:4',
        'bawah_lebar' => 'decimal:4',
        'bawah_tinggi' => 'decimal:4',

        'includeBoard' => 'boolean',
        'includeCoverLuar' => 'boolean',
        'includeCoverDalam' => 'boolean',
        'includeBusa' => 'boolean',

        'panjang_board_atas' => 'decimal:4', 'lebar_board_atas' => 'decimal:4',
        'panjang_board_bawah' => 'decimal:4', 'lebar_board_bawah' => 'decimal:4',
        'panjang_board_kuping' => 'decimal:4', 'lebar_board_kuping' => 'decimal:4',
        'panjang_board_lidah' => 'decimal:4', 'lebar_board_lidah' => 'decimal:4',
        'panjang_board_selongsong' => 'decimal:4', 'lebar_board_selongsong' => 'decimal:4',
        'board_panjang_kertas' => 'decimal:4', 'board_lebar_kertas' => 'decimal:4',

        'panjang_cover_luar_atas' => 'decimal:4', 'lebar_cover_luar_atas' => 'decimal:4',
        'panjang_cover_luar_bawah' => 'decimal:4', 'lebar_cover_luar_bawah' => 'decimal:4',
        'panjang_cover_luar_kuping' => 'decimal:4', 'lebar_cover_luar_kuping' => 'decimal:4',
        'panjang_cover_luar_lidah' => 'decimal:4', 'lebar_cover_luar_lidah' => 'decimal:4',
        'panjang_cover_luar_selongsong' => 'decimal:4', 'lebar_cover_luar_selongsong' => 'decimal:4',
        'cover_luar_panjang_kertas' => 'decimal:4', 'cover_luar_lebar_kertas' => 'decimal:4',

        'panjang_cover_dalam_atas' => 'decimal:4', 'lebar_cover_dalam_atas' => 'decimal:4',
        'panjang_cover_dalam_bawah' => 'decimal:4', 'lebar_cover_dalam_bawah' => 'decimal:4',
        'panjang_cover_dalam_lidah' => 'decimal:4', 'lebar_cover_dalam_lidah' => 'decimal:4',
        'panjang_cover_dalam_selongsong' => 'decimal:4', 'lebar_cover_dalam_selongsong' => 'decimal:4',
        'cover_dalam_panjang_kertas' => 'decimal:4', 'cover_dalam_lebar_kertas' => 'decimal:4',

        'panjang_busa' => 'decimal:4', 'lebar_busa' => 'decimal:4',
        'busa_panjang_kertas' => 'decimal:4', 'busa_lebar_kertas' => 'decimal:4',

        'qty1_board_atas' => 'integer', 'qty2_board_atas' => 'integer', 'final_qty_board_atas' => 'integer',
        'qty1_board_bawah' => 'integer', 'qty2_board_bawah' => 'integer', 'final_qty_board_bawah' => 'integer',
        'qty1_board_kuping' => 'integer', 'qty2_board_kuping' => 'integer', 'final_qty_board_kuping' => 'integer',
        'qty1_board_lidah' => 'integer', 'qty2_board_lidah' => 'integer', 'final_qty_board_lidah' => 'integer',
        'qty1_board_selongsong' => 'integer', 'qty2_board_selongsong' => 'integer', 'final_qty_board_selongsong' => 'integer',

        'qty1_cover_luar_atas' => 'integer', 'qty2_cover_luar_atas' => 'integer', 'final_qty_cover_luar_atas' => 'integer',
        'qty1_cover_luar_bawah' => 'integer', 'qty2_cover_luar_bawah' => 'integer', 'final_qty_cover_luar_bawah' => 'integer',
        'qty1_cover_luar_kuping' => 'integer', 'qty2_cover_luar_kuping' => 'integer', 'final_qty_cover_luar_kuping' => 'integer',
        'qty1_cover_luar_lidah' => 'integer', 'qty2_cover_luar_lidah' => 'integer', 'final_qty_cover_luar_lidah' => 'integer',
        'qty1_cover_luar_selongsong' => 'integer', 'qty2_cover_luar_selongsong' => 'integer', 'final_qty_cover_luar_selongsong' => 'integer',

        'qty1_cover_dalam_atas' => 'integer', 'qty2_cover_dalam_atas' => 'integer', 'final_qty_cover_dalam_atas' => 'integer',
        'qty1_cover_dalam_bawah' => 'integer', 'qty2_cover_dalam_bawah' => 'integer', 'final_qty_cover_dalam_bawah' => 'integer',
        'qty1_cover_dalam_lidah' => 'integer', 'qty2_cover_dalam_lidah' => 'integer', 'final_qty_cover_dalam_lidah' => 'integer',
        'qty1_cover_dalam_selongsong' => 'integer', 'qty2_cover_dalam_selongsong' => 'integer', 'final_qty_cover_dalam_selongsong' => 'integer',

        'qty1_busa' => 'integer', 'qty2_busa' => 'integer', 'final_qty_busa' => 'integer',

        'unit_price_board_atas' => 'decimal:4', 'unit_price_board_bawah' => 'decimal:4',
        'unit_price_board_kuping' => 'decimal:4', 'unit_price_board_lidah' => 'decimal:4',
        'unit_price_board_selongsong' => 'decimal:4',

        'unit_price_cover_luar_atas' => 'decimal:4', 'unit_price_cover_luar_bawah' => 'decimal:4',
        'unit_price_cover_luar_kuping' => 'decimal:4', 'unit_price_cover_luar_lidah' => 'decimal:4',
        'unit_price_cover_luar_selongsong' => 'decimal:4',

        'unit_price_cover_dalam_atas' => 'decimal:4', 'unit_price_cover_dalam_bawah' => 'decimal:4',
        'unit_price_cover_dalam_lidah' => 'decimal:4', 'unit_price_cover_dalam_selongsong' => 'decimal:4',

        'unit_price_busa' => 'decimal:4',

        'total_price_estimate_numeric' => 'decimal:2',
    ];

    /**
     * Get the production item selected for the board.
     */
    public function itemBoard(): BelongsTo
    {
        return $this->belongsTo(ProductionItem::class, 'selected_item_board');
    }

    /**
     * Get the production item selected for the outer cover.
     */
    public function itemCoverLuar(): BelongsTo
    {
        return $this->belongsTo(ProductionItem::class, 'selected_item_cover_luar');
    }

    /**
     * Get the production item selected for the inner cover.
     */
    public function itemCoverDalam(): BelongsTo
    {
        return $this->belongsTo(ProductionItem::class, 'selected_item_cover_dalam');
    }

    /**
     * Get the production item selected for the foam.
     */
    public function itemBusa(): BelongsTo
    {
        return $this->belongsTo(ProductionItem::class, 'selected_item_busa');
    }

    /**
     * Get the user who created this calculation.
     * Uncomment jika Anda menggunakan user_id
     */
    // public function user(): BelongsTo
    // {
    // return $this->belongsTo(User::class, 'user_id');
    // }
}