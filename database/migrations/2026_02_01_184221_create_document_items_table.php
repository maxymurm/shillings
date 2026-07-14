<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignUuid('account_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 4)->default(1);
            $table->bigInteger('price_num')->default(0);
            $table->bigInteger('price_denom')->default(100);
            $table->enum('discount_type', ['percentage', 'fixed'])->nullable();
            $table->bigInteger('discount_num')->default(0);
            $table->bigInteger('discount_denom')->default(100);
            $table->uuid('tax_id')->nullable(); // FK added in 184240_add_deferred_fk_constraints
            $table->bigInteger('subtotal_num')->default(0);
            $table->bigInteger('subtotal_denom')->default(100);
            $table->bigInteger('tax_total_num')->default(0);
            $table->bigInteger('tax_total_denom')->default(100);
            $table->bigInteger('total_num')->default(0);
            $table->bigInteger('total_denom')->default(100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['document_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_items');
    }
};
