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
        Schema::table('invoice_product', function (Blueprint $table) {
            $table->string('item_type')->default('existing')->after('product_id')->nullable();
            $table->decimal('panjang', 8, 2)->nullable()->after('item_type');
            $table->decimal('lebar', 8, 2)->nullable()->after('panjang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_product', function (Blueprint $table) {
            $table->dropColumn(['item_type', 'panjang', 'lebar']);
        });
    }
};
