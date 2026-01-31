<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Account balances cache table for performance optimization.
     * Stores pre-calculated balances to avoid summing splits on every query.
     */
    public function up(): void
    {
        Schema::create('account_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('currency_id')->constrained();

            // GnuCash-style balance storage with numerator/denominator
            $table->bigInteger('balance_num')->default(0);
            $table->integer('balance_denom')->default(100);

            // Running totals for performance
            $table->bigInteger('debits_num')->default(0);
            $table->integer('debits_denom')->default(100);
            $table->bigInteger('credits_num')->default(0);
            $table->integer('credits_denom')->default(100);

            // Last transaction included in this balance
            $table->foreignUuid('last_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamp('last_transaction_date')->nullable();

            // Split count for validation
            $table->unsignedInteger('split_count')->default(0);

            // Recalculation tracking
            $table->timestamp('calculated_at')->nullable();
            $table->boolean('is_stale')->default(false);

            $table->timestamps();

            // One balance record per account per currency
            $table->unique(['account_id', 'currency_id']);
            $table->index(['account_id', 'is_stale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_balances');
    }
};
