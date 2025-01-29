<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSavingsProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('savings_products', function (Blueprint $table) {
            $table->id(); 
            $table->string('savings_product'); 
            $table->unsignedBigInteger('company_id'); // derive from company table
            $table->unsignedBigInteger('frequency_id')->nullable();  // derive from frequencies table
            $table->integer('duration')->nullable(); 
         
             $table->double('interest_rate', 5, 2)->nullable(); 
             $table->double('minimum_withdraw', 9, 2)->nullable(); 
             $table->double('max_withdraw', 15, 2)->nullable(); 
             $table->double('maximum_deposit', 15, 2)->nullable(); 
             $table->double('maintenance_charges', 9, 2)->nullable(); 
             $table->double('daily_withdraw_limit_count', 9, 2)->nullable(); 
             $table->double('min_amt_for_interest', 9, 2)->nullable(); 
             $table->double('minimum_balance', 9, 2)->nullable(); 
             $table->double('registration_fee', 9, 2)->nullable(); 
             $table->integer('maintenace_duration')->nullable(); 
             $table->integer('bank_transfer_charge_type')->comment("0 - Flat Charge, 1 - Percentage charge"); 
             $table->integer('min_bank_transfer_charge')->nullable(); 
             $table->integer('percentage_transfer_charge')->nullable(); 
             $table->integer('max_bank_transfer_charge')->nullable(); 
            
             $table->unsignedBigInteger('created_by');
           
             $table->integer('modified_by')->nullable();// user table constrain
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
        Schema::dropIfExists('savings_products');
    }
}
