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
        Schema::table('company_settings', function (Blueprint $table) {
    $table->decimal('target_savings_interest_rate', 10, 4)
        ->default(0)
        ->after('company_id');

    $table->decimal('target_savings_early_withdrawal_fee_percentage', 10, 4)
        ->default(0)
        ->after('target_savings_interest_rate');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['target_savings_interest_rate', 'target_savings_early_withdrawal_fee_percentage']);
        });
    }
};
