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
        Schema::table('company_financial_entries', function (Blueprint $table) {
            $table->foreignId('fixed_savings_id')
                ->nullable()
                ->after('campaign_id')
                ->constrained('fixed_savings')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_financial_entries', function (Blueprint $table) {
            $table->dropForeign([
                'fixed_savings_id',
            ]);

            $table->dropColumn('fixed_savings_id');
        });
    }
};