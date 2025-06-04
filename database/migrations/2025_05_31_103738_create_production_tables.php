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
            $table->string('product_name'); // Required by default for string
            $table->string('box_type_selection'); // Required by default for string
            $table->integer('quantity')->default(1);
            $table->string('include_knife_cost')->default('tidak_ada'); // 'ada' atau 'tidak_ada'

            // Input Dimensi Box (all nullable)
            $table->float('atas_panjang', 8, 2)->nullable();
            $table->float('atas_lebar', 8, 2)->nullable();
            $table->float('atas_tinggi', 8, 2)->nullable();
            $table->float('bawah_panjang', 8, 2)->nullable();
            $table->float('bawah_lebar', 8, 2)->nullable();
            $table->float('bawah_tinggi', 8, 2)->nullable();

            // Komponen yang disertakan (Flags)
            $table->boolean('is_board_included')->default(false);
            $table->boolean('is_cover_luar_included')->default(false);
            $table->boolean('is_cover_dalam_included')->default(false);
            $table->boolean('is_busa_included')->default(false);

            // Item Produksi yang Dipilih (menyimpan ID ProductionItem)
            $table->json('selected_items_ids')->nullable()->comment('e.g., {"board": 1, "cover_luar": 2}');

            // Dimensi Bahan Baku yang Digunakan (all nullable)
            $table->float('raw_board_panjang_kertas', 8, 2)->nullable();
            $table->float('raw_board_lebar_kertas', 8, 2)->nullable();
            $table->float('raw_cl_panjang_kertas', 8, 2)->nullable(); // cl = cover luar
            $table->float('raw_cl_lebar_kertas', 8, 2)->nullable();
            $table->float('raw_cd_panjang_kertas', 8, 2)->nullable(); // cd = cover dalam
            $table->float('raw_cd_lebar_kertas', 8, 2)->nullable();
            $table->float('raw_busa_panjang_material', 8, 2)->nullable();
            $table->float('raw_busa_lebar_material', 8, 2)->nullable();

            // Dimensi Potongan Jadi - BOARD (dim_MATERIAL_PART_p/l) (all nullable)
            $table->float('dim_board_atas_p', 8, 2)->nullable(); $table->float('dim_board_atas_l', 8, 2)->nullable();
            $table->float('dim_board_bawah_p', 8, 2)->nullable(); $table->float('dim_board_bawah_l', 8, 2)->nullable();
            $table->float('dim_board_kuping_p', 8, 2)->nullable(); $table->float('dim_board_kuping_l', 8, 2)->nullable();
            $table->float('dim_board_lidah_p', 8, 2)->nullable(); $table->float('dim_board_lidah_l', 8, 2)->nullable();
            $table->float('dim_board_selongsong_p', 8, 2)->nullable(); $table->float('dim_board_selongsong_l', 8, 2)->nullable();

            // Dimensi Potongan Jadi - COVER LUAR (all nullable)
            $table->float('dim_cl_atas_p', 8, 2)->nullable(); $table->float('dim_cl_atas_l', 8, 2)->nullable();
            $table->float('dim_cl_bawah_p', 8, 2)->nullable(); $table->float('dim_cl_bawah_l', 8, 2)->nullable();
            $table->float('dim_cl_kuping_p', 8, 2)->nullable(); $table->float('dim_cl_kuping_l', 8, 2)->nullable();
            $table->float('dim_cl_lidah_p', 8, 2)->nullable(); $table->float('dim_cl_lidah_l', 8, 2)->nullable();
            $table->float('dim_cl_selongsong_p', 8, 2)->nullable(); $table->float('dim_cl_selongsong_l', 8, 2)->nullable();

            // Dimensi Potongan Jadi - COVER DALAM (all nullable)
            $table->float('dim_cd_atas_p', 8, 2)->nullable(); $table->float('dim_cd_atas_l', 8, 2)->nullable(); // Termasuk kuping jika Jendela
            $table->float('dim_cd_bawah_p', 8, 2)->nullable(); $table->float('dim_cd_bawah_l', 8, 2)->nullable();
            $table->float('dim_cd_lidah_p', 8, 2)->nullable(); $table->float('dim_cd_lidah_l', 8, 2)->nullable();
            $table->float('dim_cd_selongsong_p', 8, 2)->nullable(); $table->float('dim_cd_selongsong_l', 8, 2)->nullable();

            // Dimensi Potongan Jadi - BUSA (all nullable)
            $table->float('dim_busa_p', 8, 2)->nullable(); $table->float('dim_busa_l', 8, 2)->nullable();

            // Kuantitas Final dari Bahan (final_qty_MATERIAL_PART) (all nullable)
            $table->integer('final_qty_board_atas')->nullable();
            $table->integer('final_qty_board_bawah')->nullable();
            $table->integer('final_qty_board_kuping')->nullable();
            $table->integer('final_qty_board_lidah')->nullable();
            $table->integer('final_qty_board_selongsong')->nullable();
            $table->integer('final_qty_cl_atas')->nullable();
            $table->integer('final_qty_cl_bawah')->nullable();
            $table->integer('final_qty_cl_kuping')->nullable();
            $table->integer('final_qty_cl_lidah')->nullable();
            $table->integer('final_qty_cl_selongsong')->nullable();
            $table->integer('final_qty_cd_atas')->nullable(); // Termasuk kuping jika Jendela
            $table->integer('final_qty_cd_bawah')->nullable();
            $table->integer('final_qty_cd_lidah')->nullable();
            $table->integer('final_qty_cd_selongsong')->nullable();
            $table->integer('final_qty_busa')->nullable();

            // Harga Satuan per Potongan Jadi (unit_price_MATERIAL_PART) (all nullable)
            $table->decimal('unit_price_board_atas', 15, 4)->nullable(); // Presisi lebih besar
            $table->decimal('unit_price_board_bawah', 15, 4)->nullable();
            $table->decimal('unit_price_board_kuping', 15, 4)->nullable();
            $table->decimal('unit_price_board_lidah', 15, 4)->nullable();
            $table->decimal('unit_price_board_selongsong', 15, 4)->nullable();
            $table->decimal('unit_price_cl_atas', 15, 4)->nullable();
            $table->decimal('unit_price_cl_bawah', 15, 4)->nullable();
            $table->decimal('unit_price_cl_kuping', 15, 4)->nullable();
            $table->decimal('unit_price_cl_lidah', 15, 4)->nullable();
            $table->decimal('unit_price_cl_selongsong', 15, 4)->nullable();
            $table->decimal('unit_price_cd_atas', 15, 4)->nullable(); // Termasuk kuping jika Jendela
            $table->decimal('unit_price_cd_bawah', 15, 4)->nullable();
            $table->decimal('unit_price_cd_lidah', 15, 4)->nullable();
            $table->decimal('unit_price_cd_selongsong', 15, 4)->nullable();
            $table->decimal('unit_price_busa', 15, 4)->nullable();

            // Referensi Biaya & Tarif yang Digunakan (all nullable, kecuali yang wajib)
            $table->string('master_cost_size_selected')->nullable();
            $table->decimal('master_cost_production_rate', 15, 2)->nullable()->comment('Tarif ongkos kerja per unit dari master_costs');
            $table->decimal('master_cost_knife_rate', 15, 2)->nullable()->comment('Tarif ongkos pisau dari master_costs');
            $table->decimal('master_cost_profit_percentage', 5, 2)->nullable()->comment('Persentase profit dari master_costs');
            $table->string('poly_dimension_selected')->nullable();
            $table->decimal('poly_cost_rate', 15, 2)->nullable()->comment('Tarif poly per unit/dimensi dari poly_costs');

            // Ringkasan Biaya Akhir (summary_) (all nullable)
            $table->decimal('summary_total_material_cost', 15, 2)->nullable();
            $table->decimal('summary_total_production_work_cost', 15, 2)->nullable();
            $table->decimal('summary_total_poly_cost', 15, 2)->nullable();
            $table->decimal('summary_actual_knife_cost', 15, 2)->nullable();
            $table->decimal('summary_profit_percentage_applied', 5, 2)->nullable();
            $table->decimal('summary_total_profit_amount', 15, 2)->nullable();
            $table->decimal('summary_selling_price_per_item', 15, 2)->nullable();

            // Total Estimasi (all nullable)
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