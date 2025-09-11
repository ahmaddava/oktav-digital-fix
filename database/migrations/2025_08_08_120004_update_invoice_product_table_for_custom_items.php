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
        // Targetkan nama tabel yang benar: 'invoice_product'
        Schema::table('invoice_product', function (Blueprint $table) {
            // 1. Tambahkan kolom baru untuk nama produk kustom, letakkan setelah product_id
            $table->string('product_name')->nullable()->after('product_id');

            // 2. Ubah kolom product_id yang sudah ada agar bisa bernilai null
            $table->foreignId('product_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_product', function (Blueprint $table) {
            // Jika di-rollback, kembalikan seperti semula
            $table->dropColumn('product_name');
            $table->foreignId('product_id')->nullable(false)->change();
        });
    }
};
