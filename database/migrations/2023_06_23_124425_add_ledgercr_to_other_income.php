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
        Schema::table('other_incomes', function (Blueprint $table) {
            $table->unsignedBigInteger('ledger_cr')->nullable();
            $table->foreign('ledger_cr')->references('id')->on('ledger_accounts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('other_income', function (Blueprint $table) {
            //
        });
    }
};
