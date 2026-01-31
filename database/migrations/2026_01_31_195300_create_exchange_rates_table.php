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
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('from_currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->foreignUuid('to_currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->bigInteger('rate_num'); // Numerator of rate fraction
            $table->bigInteger('rate_denom')->default(1000000); // Denominator (6 decimal precision)
            $table->date('effective_date');
            $table->string('source', 50)->nullable(); // e.g., 'manual', 'api', 'ecb', 'openexchange'
            $table->timestamps();

            // Unique constraint: one rate per currency pair per date
            $table->unique(['from_currency_id', 'to_currency_id', 'effective_date'], 'exchange_rates_pair_date_unique');

            // Index for lookups
            $table->index(['from_currency_id', 'to_currency_id', 'effective_date'], 'exchange_rates_lookup');
        });

        // Add value_currency_id to splits for explicit currency tracking in multi-currency transactions
        Schema::table('splits', function (Blueprint $table) {
            $table->foreignUuid('value_currency_id')
                ->nullable()
                ->after('value_denom')
                ->constrained('currencies')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('splits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('value_currency_id');
        });

        Schema::dropIfExists('exchange_rates');
    }
};
