<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_wallets', function (Blueprint $table) {
            $table->double('subscription_balance', 15, 8);
            $table->string('subscription_vaccount')->nullable();
            $table->string('card_vaccount')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_wallets', function (Blueprint $table) {
            //
        });
    }
};
