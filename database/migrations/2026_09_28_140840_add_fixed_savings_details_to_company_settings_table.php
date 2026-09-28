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
            $table->decimal('fixed_saving_interest', 15, 2)->default(0.00)->after('id');
            $table->decimal('fixed_saving_commission', 15, 2)->default(0.00)->after('fixed_saving_interest');
            $table->decimal('fixed_savings_early_withdrawal_fee_percentage', 5, 2)->default(0.00)->after('fixed_saving_commission');
            $table->decimal('fixed_savings_interest_rate', 5, 2)->default(0.00)->after('fixed_savings_early_withdrawal_fee_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'fixed_saving_interest',
                'fixed_saving_commission',
                'fixed_savings_early_withdrawal_fee_percentage',
                'fixed_savings_interest_rate'
            ]);
        });
    }
};
