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
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedTinyInteger('fiscal_year_end_month')->default(12)->after('fiscal_year_start');
            $table->unsignedTinyInteger('fiscal_year_end_day')->default(31)->after('fiscal_year_end_month');
            $table->boolean('is_active')->default(true)->after('fiscal_year_end_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['fiscal_year_end_month', 'fiscal_year_end_day', 'is_active']);
        });
    }
};
