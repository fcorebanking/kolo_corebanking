<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ajo_members', function (Blueprint $table) {
            if (!Schema::hasColumn('ajo_members', 'pending_collection_amount')) {
                $table->decimal('pending_collection_amount', 20, 2)
                    ->default(0)
                    ->after('total_contributed');
            }

            if (!Schema::hasColumn('ajo_members', 'pending_insufficient_balance_fees')) {
                $table->decimal('pending_insufficient_balance_fees', 20, 2)
                    ->default(0)
                    ->after('insufficient_balance_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ajo_members', function (Blueprint $table) {
            if (Schema::hasColumn('ajo_members', 'pending_collection_amount')) {
                $table->dropColumn('pending_collection_amount');
            }

            if (Schema::hasColumn('ajo_members', 'pending_insufficient_balance_fees')) {
                $table->dropColumn('pending_insufficient_balance_fees');
            }
        });
    }
};
