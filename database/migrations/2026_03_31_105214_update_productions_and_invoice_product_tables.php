<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->foreignId('machine_id')->nullable()->after('invoice_id')->constrained('machines')->onDelete('set null');
        });

        Schema::table('invoice_product', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('product_name');
        });

        // Data migration
        DB::table('machines')->insert([
            ['id' => 1, 'name' => 'Mesin 1', 'use_clicks' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Mesin 2', 'use_clicks' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('productions')->where('machine_type', 'mesin_1')->update(['machine_id' => 1]);
        DB::table('productions')->where('machine_type', 'mesin_2')->update(['machine_id' => 2]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->dropForeign(['machine_id']);
            $table->dropColumn('machine_id');
        });

        Schema::table('invoice_product', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
