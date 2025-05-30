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
        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->nullable()->constrained();
            $table->enum('machine_type', ['mesin_1', 'mesin_2'])->default('mesin_1');
            $table->enum('status', ['pending', 'started', 'completed'])->default('pending'); // Added 'started' status
            $table->integer('failed_prints')->default(0);
            $table->integer('total_clicks')->default(0);
            $table->integer('total_counter')->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_adjustment')->default(0);
            $table->integer('adjustment_value')->nullable();
            $table->timestamp('started_at')->nullable(); // New: Timestamp for when production starts
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('deadline')->nullable(); // New: Deadline for the production
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productions');
    }
};