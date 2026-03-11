<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('budget_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('account_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('period_num');
            $table->bigInteger('amount_num')->default(0);
            $table->bigInteger('amount_denom')->default(100);
            $table->timestamps();

            $table->unique(['budget_id', 'account_id', 'period_num']);
            $table->index(['budget_id', 'period_num']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_accounts');
    }
};
