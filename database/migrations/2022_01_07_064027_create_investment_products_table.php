<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvestmentProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('investment_products', function (Blueprint $table) {
         
                $table->id(); 
                $table->string('product'); 
                $table->string('cover_pic'); 
                $table->unsignedBigInteger('company_id');  
 
                $table->unsignedBigInteger('frequency_id')->nullable();  // derive from frequencies table
                $table->foreign('frequency_id')->references('id')->on('frequencies'); 

                $table->integer('duration')->nullable(); 
             
                 $table->double('interest_rate', 5, 2)->nullable(); 
                 
                 $table->unsignedBigInteger('status_id');  // derive from bank table
                 $table->foreign('status_id')->references('id')->on('company_statuses'); 

                 $table->double('minimum_investment', 9, 2)->nullable(); 
                 $table->double('maximum_investment', 9, 2)->nullable(); 
                 $table->double('termination_charge', 5, 2)->nullable(); 
              
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
        Schema::dropIfExists('investment_products');
    }
}
