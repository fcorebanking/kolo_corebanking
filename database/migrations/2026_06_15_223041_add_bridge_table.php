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
        Schema::table('company_wallets', function (Blueprint $table) {
            $table->string('bridge_company_id')->nullable();
            $table->string('bridge_company_address')->nullable();
            $table->string('bridge_company_chain')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_wallets', function (Blueprint $table) {
            //
        });
    }
};
