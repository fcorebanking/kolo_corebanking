<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('wb_account_detail_id')->nullable();  // initiator user wb_account_details table
            $table->foreign('wb_account_detail_id')->references('id')->on('wb_account_details')->onDelete('cascade')->change();
            $table->unsignedBigInteger('ecomerce_product_model_id');
            $table->foreign('ecomerce_product_model_id')->references('id')->on('ecomerce_product_models');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->foreign('transaction_id')->references('id')->on('transactions');
            $table->unsignedBigInteger('delivery_detail_model_id')->nullable();
            $table->foreign('delivery_detail_model_id')->references('id')->on('delivery_detail_models');
            $table->integer('qrty')->default(1);
            $table->integer('status_id')->default(2);
            $table->double('amount', 15, 2);
            $table->double('total_amount', 15, 2);
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
        Schema::dropIfExists('order_models');
    }
}
