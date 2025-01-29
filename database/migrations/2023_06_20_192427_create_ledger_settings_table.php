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
        Schema::create('ledger_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('company');
            $table->unsignedBigInteger('transaction_type_id');
            $table->foreign('transaction_type_id')->references('id')->on('transaction_types');
            $table->unsignedBigInteger('ledger_cr');
            $table->foreign('ledger_cr')->references('id')->on('ledger_accounts');
            $table->unsignedBigInteger('ledger_dr');
            $table->foreign('ledger_dr')->references('id')->on('ledger_accounts');
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ledger_settings');
    }
};
