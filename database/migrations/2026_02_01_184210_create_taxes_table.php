<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->bigInteger('rate_num')->default(0);
            $table->bigInteger('rate_denom')->default(100);
            $table->enum('type', ['percentage', 'fixed'])->default('percentage');
            $table->boolean('is_compound')->default(false);
            $table->boolean('is_recoverable')->default(true);
            $table->foreignUuid('account_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
