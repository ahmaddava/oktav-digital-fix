<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ProductionCategory; // Pastikan Anda mengimpor model ini

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tambahkan kategori jika belum ada
        ProductionCategory::firstOrCreate(['name' => 'Board'], ['is_active' => true]);
        ProductionCategory::firstOrCreate(['name' => 'Cover Luar'], ['is_active' => true]);
        ProductionCategory::firstOrCreate(['name' => 'Cover Dalam'], ['is_active' => true]);
        ProductionCategory::firstOrCreate(['name' => 'Busa'], ['is_active' => true]);
        // Tambahkan kategori lain yang Anda butuhkan di sini
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Opsional: Hapus kategori saat rollback
        // ProductionCategory::whereIn('name', ['Board', 'Cover Luar', 'Cover Dalam', 'Cover Luar Lidah', 'Busa'])->delete();
    }
};
