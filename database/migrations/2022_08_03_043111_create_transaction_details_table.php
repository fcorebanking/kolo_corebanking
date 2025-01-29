<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('transaction_id'); //constrain to transaction_type table
            $table->foreign('transaction_id')->references('id')->on('transactions');

            $table->unsignedBigInteger('bank_id')->nullable();
            $table->unsignedBigInteger('account_number')->nullable();
            $table->unsignedBigInteger('network_provider')->nullable();
            $table->unsignedBigInteger('data_sub_plan')->nullable();
            $table->unsignedBigInteger('phone_number')->nullable();
            $table->unsignedBigInteger('cable_tv_provider')->nullable();
            $table->unsignedBigInteger('cable_smart_card_number')->nullable();

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
        Schema::dropIfExists('transaction_details');
    }
}
