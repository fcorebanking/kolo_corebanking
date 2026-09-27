<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('ajo_members', 'ajo_account_id')) {
            return;
        }

        Schema::table('ajo_members', function (Blueprint $table) {
            $table->dropForeign(['ajo_account_id']);
        });

        Schema::table('ajo_members', function (Blueprint $table) {
            $table->dropColumn('ajo_account_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('ajo_members', 'ajo_account_id')) {
            return;
        }

        Schema::table('ajo_members', function (Blueprint $table) {
            $table->unsignedBigInteger('ajo_account_id')->nullable();

            $table->foreign('ajo_account_id')
                ->references('id')
                ->on('ajo_accounts')
                ->nullOnDelete();
        });
    }
};