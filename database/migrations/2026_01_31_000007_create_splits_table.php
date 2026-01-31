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
        Schema::create('splits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')
                ->constrained('transactions')
                ->cascadeOnDelete();
            $table->foreignUuid('account_id')
                ->constrained('accounts')
                ->restrictOnDelete();

            // GnuCash-style precision arithmetic using numerator/denominator
            // amount = amount_num / amount_denom (in account's currency)
            $table->bigInteger('amount_num')->default(0);
            $table->bigInteger('amount_denom')->default(100);

            // value = value_num / value_denom (in transaction's currency)
            $table->bigInteger('value_num')->default(0);
            $table->bigInteger('value_denom')->default(100);

            $table->string('action', 10); // DEBIT or CREDIT
            $table->string('memo', 500)->nullable();

            // Reconciliation tracking
            // n = not reconciled, c = cleared, y = reconciled, f = frozen, v = void
            $table->char('reconciled_state', 1)->default('n');
            $table->date('reconcile_date')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['transaction_id']);
            $table->index(['account_id', 'reconciled_state']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('splits');
    }
};
