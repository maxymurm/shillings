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
        Schema::create('custom_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('base_report'); // trial_balance, income_statement, etc.
            
            // Configuration stored as JSON
            $table->json('account_ids')->nullable(); // Selected accounts
            $table->json('columns')->nullable(); // Selected columns
            $table->json('grouping')->nullable(); // Grouping options
            $table->json('filters')->nullable(); // Additional filters
            $table->json('date_range')->nullable(); // Date range config
            
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_shared')->default(false);
            $table->integer('run_count')->default(0);
            $table->timestamp('last_run_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'is_favorite']);
            $table->index(['company_id', 'is_shared']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_reports');
    }
};
