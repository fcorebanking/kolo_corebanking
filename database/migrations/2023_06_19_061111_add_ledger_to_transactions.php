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

        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('ledger_cr')->nullable();
            $table->foreign('ledger_cr')->references('id')->on('ledger_accounts');
            $table->unsignedBigInteger('ledger_dr')->nullable();
            $table->foreign('ledger_dr')->references('id')->on('ledger_accounts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            //
        });
    }
};
