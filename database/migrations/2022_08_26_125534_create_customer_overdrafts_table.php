<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerOverdraftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_overdrafts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->unsignedBigInteger('frequency_id');
            $table->foreign('frequency_id')->references('id')->on('frequencies');

            $table->unsignedBigInteger('transaction_id');
            $table->foreign('transaction_id')->references('id')->on('transactions');

            $table->double('interest', 4, 2); //interest amount
            $table->double('amount', 15, 2); //transaction amount
            $table->double('amount_remaing', 15, 2)->default(0); //transaction amount

            $table->unsignedBigInteger('status_id')->nullable()->default(7);
            $table->foreign('status_id')->references('id')->on('statuses'); // user derive from status table

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
        Schema::dropIfExists('customer_overdrafts');
    }
}
