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
        Schema::table('transactions', function (Blueprint $table) {
            // Reversal tracking
            $table->foreignUuid('reverses_id')->nullable()->after('created_by_id')
                ->constrained('transactions')->nullOnDelete();
            $table->foreignUuid('reversed_by_id')->nullable()->after('reverses_id')
                ->constrained('transactions')->nullOnDelete();

            // Void tracking
            $table->boolean('is_void')->default(false)->after('is_posted');
            $table->string('void_reason')->nullable()->after('is_void');
            $table->timestamp('voided_at')->nullable()->after('void_reason');
            $table->foreignUuid('voided_by_id')->nullable()->after('voided_at')
                ->constrained('users')->nullOnDelete();

            // Posted timestamp
            $table->timestamp('posted_at')->nullable()->after('is_posted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['reverses_id']);
            $table->dropForeign(['reversed_by_id']);
            $table->dropForeign(['voided_by_id']);
            $table->dropColumn([
                'reverses_id',
                'reversed_by_id',
                'is_void',
                'void_reason',
                'voided_at',
                'voided_by_id',
                'posted_at',
            ]);
        });
    }
};
