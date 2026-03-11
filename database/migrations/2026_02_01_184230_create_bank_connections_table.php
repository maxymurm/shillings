<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_connections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained()->cascadeOnDelete();
            $table->enum('provider', ['manual', 'plaid', 'yodlee'])->default('manual');
            $table->text('credentials')->nullable();
            $table->string('institution_name')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->enum('sync_status', ['pending', 'syncing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['account_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_connections');
    }
};
