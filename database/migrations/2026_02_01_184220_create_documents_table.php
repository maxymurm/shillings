<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['invoice', 'bill', 'quote', 'credit_note', 'debit_note'])->index();
            $table->string('document_number');
            $table->string('order_number')->nullable();
            $table->foreignUuid('contact_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['draft', 'sent', 'viewed', 'partial', 'paid', 'cancelled', 'overdue', 'converted'])->default('draft');
            $table->date('issued_at');
            $table->date('due_at');
            $table->string('currency_code', 3);
            $table->bigInteger('exchange_rate_num')->default(1);
            $table->bigInteger('exchange_rate_denom')->default(1);
            $table->bigInteger('subtotal_num')->default(0);
            $table->bigInteger('subtotal_denom')->default(100);
            $table->bigInteger('tax_total_num')->default(0);
            $table->bigInteger('tax_total_denom')->default(100);
            $table->bigInteger('discount_num')->default(0);
            $table->bigInteger('discount_denom')->default(100);
            $table->bigInteger('total_num')->default(0);
            $table->bigInteger('total_denom')->default(100);
            $table->bigInteger('amount_paid_num')->default(0);
            $table->bigInteger('amount_paid_denom')->default(100);
            $table->text('notes')->nullable();
            $table->text('footer')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignUuid('bill_term_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('parent_id')->nullable();   // FK added separately below
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'type', 'document_number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'contact_id']);
            $table->index(['company_id', 'due_at']);
            $table->foreign('currency_code')->references('code')->on('currencies');
        });

        // Add self-referential FK separately (PostgreSQL requires PK to be committed first)
        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('documents')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
