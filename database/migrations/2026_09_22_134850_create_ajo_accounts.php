<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajo_accounts', function (Blueprint $table) {
            $table->id();

            // Legacy Finova user/company IDs are stored as unsigned integers.
            // They are intentionally not constrained with foreign keys so this
            // migration does not force a type change on the existing schema.
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('company_id');

            /*
             * This is the user's permanent personal Ajo Account.
             * It is deliberately separate from the main Finova wallet.
             *
             * DECIMAL is signed by default, so negative balances are allowed.
             * Negative balances are part of the insufficient-balance rule.
             */
            $table->decimal('balance', 20, 2)->default(0);
            $table->string('status', 30)->default('active');

            $table->timestamps();

            $table->unique('user_id');
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajo_accounts');
    }
};