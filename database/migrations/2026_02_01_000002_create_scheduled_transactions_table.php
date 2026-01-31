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
        Schema::create('scheduled_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('template_id')->nullable()->constrained('transaction_templates')->nullOnDelete();
            $table->foreignUuid('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Transaction details
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignUuid('currency_id')->nullable()->constrained()->nullOnDelete();
            $table->json('splits_data'); // JSON array of split data
            
            // Schedule settings
            $table->string('frequency'); // daily, weekly, bi-weekly, monthly, quarterly, yearly
            $table->integer('frequency_interval')->default(1); // every N periods
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_occurrence');
            $table->date('last_occurrence')->nullable();
            
            // Day-specific options
            $table->integer('day_of_week')->nullable(); // 0-6 for weekly
            $table->integer('day_of_month')->nullable(); // 1-31 for monthly
            $table->integer('month_of_year')->nullable(); // 1-12 for yearly
            
            // Behavior options
            $table->boolean('auto_create')->default(false); // Auto-create or just remind
            $table->boolean('auto_post')->default(false); // Auto-post after creation
            $table->boolean('is_active')->default(true);
            $table->boolean('is_paused')->default(false);
            
            // Tracking
            $table->integer('occurrences_created')->default(0);
            $table->integer('max_occurrences')->nullable(); // Stop after N occurrences
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'is_active', 'next_occurrence']);
            $table->index(['next_occurrence', 'auto_create']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_transactions');
    }
};
