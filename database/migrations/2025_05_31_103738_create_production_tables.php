<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
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
            $table->string('size'); // XS, Kecil, Sedang, Besar, XXL
            $table->json('selected_items'); // menyimpan item yang dipilih
            $table->decimal('total_material_cost', 12, 2);
            $table->decimal('production_cost', 12, 2);
            $table->decimal('poly_cost', 12, 2)->nullable();
            $table->decimal('knife_cost', 12, 2)->nullable();

            // Dimensi Box Atas
            $table->float('panjang_atas', 8, 2)->nullable();
            $table->float('lebar_atas', 8, 2)->nullable();
            $table->float('tinggi_atas', 8, 2)->nullable();

            // Dimensi Box Bawah
            $table->float('panjang_bawah', 8, 2)->nullable();
            $table->float('lebar_bawah', 8, 2)->nullable();
            $table->float('tinggi_bawah', 8, 2)->nullable();
            
            // Jika ingin menyimpan hasil perhitungan board, tambahkan kolom ini (opsional)
            $table->integer('board_panjang_atas')->nullable();
            $table->integer('board_panjang_bawah')->nullable();
            $table->integer('board_lebar_bawah')->nullable();
            $table->integer('board_lebar_atas')->nullable();

            $table->decimal('profit', 12, 2);
            $table->decimal('total_price', 12, 2);
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