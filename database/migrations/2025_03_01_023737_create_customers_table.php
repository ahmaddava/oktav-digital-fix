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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('nama_customer')->nullable(false); // Wajib diisi
            $table->string('email_customer')->nullable(); // Boleh tidak diisi
            $table->string('nomor_customer')->nullable(false); // Wajib diisi
            $table->text('alamat_customer')->nullable(); // Boleh tidak diisi
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};