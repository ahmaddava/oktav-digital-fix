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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sequence_number')->unique();
            $table->string('invoice_number')->unique();  // Kolom nomor invoice
            $table->enum('status', ['paid', 'unpaid']);  // Status pembayaran
            $table->string('approval_status')
                ->default('pending')
                ->comment('pending/approved/rejected');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->string('name_customer');  // Menambahkan kolom name_customer
            $table->string('customer_email')->nullable();
            $table->string('alamat_customer')->nullable();
            $table->string('customer_phone')->nullable();  // Menambahkan kolom customer_phone
            $table->text('notes')->nullable();  // Kolom catatan
            $table->decimal('dp', 10, 2)->nullable();  // Tambahkan kolom dp
            $table->decimal('grand_total', 10, 2);  // Kolom grand_total
            $table->enum('payment_method', ['transfer', 'cash']);; 
            $table->string('attachment_path')->nullable();
            $table->timestamps();  // Kolom created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
