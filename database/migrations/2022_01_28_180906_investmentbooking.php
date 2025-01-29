<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Investmentbooking extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('investmentbooking', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wb_accountdetails_id'); //constrain from wb_accountdetails table
            $table->unsignedBigInteger('investment_product_id'); //Constrain productTable
            $table->double('amount', 15, 2)->nullable(); //amount
            $table->double('wallet', 15, 2)->nullable(); //balance
            $table->double('balance', 15, 2)->nullable(); //balance
            $table->double('expected_balance', 15, 2)->nullable(); //balance
            $table->integer('duration'); 
            $table->datetime('start_date');
            $table->date('end_date');
            $table->integer('status'); 
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
        Schema::dropIfExists('investmentbooking');
    }
}
