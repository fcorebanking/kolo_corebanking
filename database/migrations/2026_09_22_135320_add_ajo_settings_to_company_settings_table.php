<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            /*
             * Only the company controls these two Ajo settings.
             *
             * ajo_creation_fee:
             *   Fixed Naira amount charged to an Ajo creator.
             *   100% belongs to the company.
             *
             * ajo_maturity_fee_percentage:
             *   Percentage charged against each member's Ajo balance at maturity.
             *   The resulting fee is split 40% company / 60% creator.
             */
            $table->decimal('ajo_creation_fee', 20, 2)->default(0);
            $table->decimal('ajo_maturity_fee_percentage', 8, 4)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'ajo_creation_fee',
                'ajo_maturity_fee_percentage',
            ]);
        });
    }
};