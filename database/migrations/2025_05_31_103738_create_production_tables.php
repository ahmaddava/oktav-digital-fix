<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabel untuk menyimpan kategori produksi
        Schema::create('production_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Cover Dalam, Cover Luar, Busa, Ongkos Produksi, dll
            $table->string('type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabel untuk menyimpan item produksi
        Schema::create('production_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('production_categories')->onDelete('cascade');
            $table->string('name'); // K30, K40, Embosindo, RCP, dll
            $table->string('size')->nullable(); // XS, Kecil, Sedang, Besar, XXL
            $table->integer('lebar_kertas')->nullable(); // lebar kertas
            $table->integer('panjang_kertas')->nullable(); // panjang kertas
            $table->string('dimension')->nullable(); // 10x10, 10x15, 15x15
            $table->decimal('price', 12, 2); // harga
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabel untuk menyimpan kalkulasi harga
        Schema::create('price_calculations', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->string('box_type_selection'); // <<<--- Kolom baru untuk Jenis Box Utama
            $table->integer('quantity')->nullable();
            $table->string('include_knife_cost')->nullable();
            
            // Input Dimensi (sudah ada dan OK)
            $table->float('atas_panjang', 8, 2)->nullable();
            $table->float('atas_lebar', 8, 2)->nullable();
            $table->float('atas_tinggi', 8, 2)->nullable();
            $table->float('bawah_panjang', 8, 2)->nullable();
            $table->float('bawah_lebar', 8, 2)->nullable();
            $table->float('bawah_tinggi', 8, 2)->nullable();

            // Item yang dipilih (JSON untuk fleksibilitas)
            $table->json('selected_items_ids')->nullable(); // e.g., {'board': 1, 'cover_luar': 2, ...}

            // Dimensi Potongan Jadi - BOARD
            $table->float('dim_board_atas_p', 8, 2)->nullable(); $table->float('dim_board_atas_l', 8, 2)->nullable();
            $table->float('dim_board_bawah_p', 8, 2)->nullable(); $table->float('dim_board_bawah_l', 8, 2)->nullable();
            $table->float('dim_board_kuping_p', 8, 2)->nullable(); $table->float('dim_board_kuping_l', 8, 2)->nullable();
            $table->float('dim_board_lidah_p', 8, 2)->nullable(); $table->float('dim_board_lidah_l', 8, 2)->nullable();
            $table->float('dim_board_selongsong_p', 8, 2)->nullable(); $table->float('dim_board_selongsong_l', 8, 2)->nullable();

            // Dimensi Potongan Jadi - COVER LUAR
            $table->float('dim_cl_atas_p', 8, 2)->nullable(); $table->float('dim_cl_atas_l', 8, 2)->nullable();
            $table->float('dim_cl_bawah_p', 8, 2)->nullable(); $table->float('dim_cl_bawah_l', 8, 2)->nullable();
            $table->float('dim_cl_kuping_p', 8, 2)->nullable(); $table->float('dim_cl_kuping_l', 8, 2)->nullable();
            $table->float('dim_cl_lidah_p', 8, 2)->nullable(); $table->float('dim_cl_lidah_l', 8, 2)->nullable();
            $table->float('dim_cl_selongsong_p', 8, 2)->nullable(); $table->float('dim_cl_selongsong_l', 8, 2)->nullable();

            // Dimensi Potongan Jadi - COVER DALAM
            $table->float('dim_cd_atas_p', 8, 2)->nullable(); $table->float('dim_cd_atas_l', 8, 2)->nullable();
            $table->float('dim_cd_bawah_p', 8, 2)->nullable(); $table->float('dim_cd_bawah_l', 8, 2)->nullable();
            // $table->float('dim_cd_kuping_p', 8, 2)->nullable(); $table->float('dim_cd_kuping_l', 8, 2)->nullable(); // Kuping CD pakai _atas
            $table->float('dim_cd_lidah_p', 8, 2)->nullable(); $table->float('dim_cd_lidah_l', 8, 2)->nullable();
            $table->float('dim_cd_selongsong_p', 8, 2)->nullable(); $table->float('dim_cd_selongsong_l', 8, 2)->nullable();

            // Dimensi Potongan Jadi - BUSA
            $table->float('dim_busa_p', 8, 2)->nullable(); $table->float('dim_busa_l', 8, 2)->nullable();

            // Kuantitas Final - BOARD
            $table->integer('final_qty_board_atas')->nullable();
            $table->integer('final_qty_board_bawah')->nullable();
            $table->integer('final_qty_board_kuping')->nullable();
            $table->integer('final_qty_board_lidah')->nullable();
            $table->integer('final_qty_board_selongsong')->nullable();

            // Kuantitas Final - COVER LUAR
            $table->integer('final_qty_cl_atas')->nullable();
            $table->integer('final_qty_cl_bawah')->nullable();
            $table->integer('final_qty_cl_kuping')->nullable();
            $table->integer('final_qty_cl_lidah')->nullable();
            $table->integer('final_qty_cl_selongsong')->nullable();

            // Kuantitas Final - COVER DALAM
            $table->integer('final_qty_cd_atas')->nullable();
            $table->integer('final_qty_cd_bawah')->nullable();
            // $table->integer('final_qty_cd_kuping')->nullable(); // Kuping CD pakai _atas
            $table->integer('final_qty_cd_lidah')->nullable();
            $table->integer('final_qty_cd_selongsong')->nullable();
            
            // Kuantitas Final - BUSA
            $table->integer('final_qty_busa')->nullable();

            // Harga Satuan - BOARD
            $table->decimal('unit_price_board_atas', 12, 2)->nullable();
            $table->decimal('unit_price_board_bawah', 12, 2)->nullable();
            $table->decimal('unit_price_board_kuping', 12, 2)->nullable();
            $table->decimal('unit_price_board_lidah', 12, 2)->nullable();
            $table->decimal('unit_price_board_selongsong', 12, 2)->nullable();

            // Harga Satuan - COVER LUAR
            $table->decimal('unit_price_cl_atas', 12, 2)->nullable();
            $table->decimal('unit_price_cl_bawah', 12, 2)->nullable();
            $table->decimal('unit_price_cl_kuping', 12, 2)->nullable();
            $table->decimal('unit_price_cl_lidah', 12, 2)->nullable();
            $table->decimal('unit_price_cl_selongsong', 12, 2)->nullable();

            // Harga Satuan - COVER DALAM
            $table->decimal('unit_price_cd_atas', 12, 2)->nullable();
            $table->decimal('unit_price_cd_bawah', 12, 2)->nullable();
            // $table->decimal('unit_price_cd_kuping', 12, 2)->nullable(); // Kuping CD pakai _atas
            $table->decimal('unit_price_cd_lidah', 12, 2)->nullable();
            $table->decimal('unit_price_cd_selongsong', 12, 2)->nullable();

            // Harga Satuan - BUSA
            $table->decimal('unit_price_busa', 12, 2)->nullable();

            // Biaya Lain dan Total
            $table->string('master_cost_size_selected')->nullable(); // Menyimpan size dari MasterCost yg dipilih
            $table->decimal('master_cost_per_unit_value', 12, 2)->nullable(); // Menyimpan nilai biaya dari MasterCost
            $table->string('poly_dimension_selected')->nullable();
            $table->decimal('poly_cost_value', 12, 2)->nullable();
            // $table->decimal('knife_cost_value', 12, 2)->nullable(); // Jika ada nilai spesifik

            $table->decimal('total_price_estimate_numeric', 15, 2)->nullable();
            $table->string('total_price_estimate_display')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('price_calculations');
        Schema::dropIfExists('production_items');
        Schema::dropIfExists('production_categories');
    }
};