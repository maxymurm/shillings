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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();
            $table->date('transaction_date');
            $table->date('post_date')->nullable();
            $table->string('description', 500)->nullable();
            $table->string('num', 50)->nullable(); // Check number, invoice number, etc.
            $table->text('notes')->nullable();
            $table->boolean('is_posted')->default(false);
            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['company_id', 'transaction_date']);
            $table->index(['company_id', 'is_posted']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
