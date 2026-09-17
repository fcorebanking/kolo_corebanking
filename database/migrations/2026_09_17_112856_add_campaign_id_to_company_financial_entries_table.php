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
            $table->foreignId('campaign_id')
                ->nullable()
                ->after('target_savings_id')
                ->constrained('campaigns')
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
                'campaign_id',
            ]);

            $table->dropColumn('campaign_id');
        });
    }
};