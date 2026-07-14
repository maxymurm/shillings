<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tax_id')->constrained()->cascadeOnDelete();
            $table->enum('applies_to', ['sales', 'purchases', 'both'])->default('both');
            $table->foreignUuid('account_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('contact_type', ['customer', 'vendor', 'employee'])->nullable();
            $table->string('region')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->timestamps();

            $table->index(['tax_id', 'applies_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rules');
    }
};
