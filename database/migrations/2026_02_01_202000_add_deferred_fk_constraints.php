<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add FK constraints that couldn't be defined inline due to migration ordering:
 * - documents.bill_term_id → bill_terms (documents runs before bill_terms)
 * - document_items.tax_id  → taxes      (document_items runs before taxes)
 * - budget_accounts.budget_id → budgets (same timestamp, alphabetical order issue)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('bill_term_id')
                ->references('id')
                ->on('bill_terms')
                ->nullOnDelete();
        });

        Schema::table('document_items', function (Blueprint $table) {
            $table->foreign('tax_id')
                ->references('id')
                ->on('taxes')
                ->nullOnDelete();
        });

        Schema::table('budget_accounts', function (Blueprint $table) {
            $table->foreign('budget_id')
                ->references('id')
                ->on('budgets')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['bill_term_id']);
        });

        Schema::table('document_items', function (Blueprint $table) {
            $table->dropForeign(['tax_id']);
        });

        Schema::table('budget_accounts', function (Blueprint $table) {
            $table->dropForeign(['budget_id']);
        });
    }
};
