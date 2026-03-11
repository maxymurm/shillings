<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_transactions', function (Blueprint $table) {
            // Only add columns that don't already exist
            if (!Schema::hasColumn('scheduled_transactions', 'reminder_days')) {
                $table->unsignedInteger('reminder_days')->default(0)->after('next_occurrence');
            }
            if (!Schema::hasColumn('scheduled_transactions', 'last_created_at')) {
                $table->timestamp('last_created_at')->nullable()->after('last_occurrence');
            }
            if (!Schema::hasColumn('scheduled_transactions', 'skip_weekends')) {
                $table->boolean('skip_weekends')->default(false)->after('auto_post');
            }
            if (!Schema::hasColumn('scheduled_transactions', 'weekend_policy')) {
                $table->enum('weekend_policy', ['before', 'after', 'skip'])->default('after')->after('skip_weekends');
            }
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_transactions', function (Blueprint $table) {
            $columns = ['reminder_days', 'last_created_at', 'skip_weekends', 'weekend_policy'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('scheduled_transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
