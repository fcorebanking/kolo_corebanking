<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ApploanSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('apploan_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('company');
            $table->unsignedBigInteger('loan_product_id');
            $table->foreign('loan_product_id')->references('id')->on('loan_products');
            $table->unsignedBigInteger('loan_type');
            $table->unsignedBigInteger('status_id')->default(1);
            $table->string('durationPeriod');
            $table->string('repayment');
            $table->string('duration');
            $table->double('maxamt',15,2);
            $table->double('minamt',15,2);
            $table->double('transactionMin',15,2);
            $table->double('transactionMax',15,2);
            $table->double('minWalletBalance',15,2);
            $table->double('maxWalletBalance',15,2);
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
        //
    }
}
