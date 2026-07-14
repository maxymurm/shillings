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
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();
            $table->uuid('parent_id')->nullable();   // FK added separately below
            $table->foreignUuid('account_type_id')
                ->constrained('account_types')
                ->restrictOnDelete();
            $table->foreignUuid('currency_id')
                ->constrained('currencies')
                ->restrictOnDelete();
            $table->string('code', 50)->nullable();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('is_placeholder')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->string('path', 1000)->nullable(); // Materialized path for hierarchy
            $table->unsignedInteger('level')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['company_id', 'account_type_id']);
            $table->index(['company_id', 'parent_id']);
            $table->unique(['company_id', 'code']);
        });

        // Add self-referential FK after the table (and its PK) are fully committed.
        // PostgreSQL requires a confirmed unique/primary key constraint on the
        // referenced column before accepting a foreign key pointing to it.
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('accounts')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
