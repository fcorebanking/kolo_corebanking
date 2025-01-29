<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWbAccountDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wb_account_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');  // initiator users table           
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('account_product_id');   //constrained to product table (savetype) or company account type         
            $table->foreign('account_product_id')->references('id')->on('savings_products');
            $table->unsignedBigInteger('bankid'); //virtual account bank
            $table->foreign('bankid')->references('id')->on('banks');
            $table->string('virtual_accnum'); //virtual account number
            $table->string('acc_reference'); //virtual account number
            $table->tinyInteger('has_atm_card'); //virtual account number

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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('wb_account_details');
        Schema::enableForeignKeyConstraints();
    }
}
