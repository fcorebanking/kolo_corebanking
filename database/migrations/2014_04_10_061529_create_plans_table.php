<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_name'); 
            $table->string('desc'); 
            $table->integer('max_customers'); 
           
            $table->integer('max_staff'); 
            $table->integer('max_branch'); 

            $table->integer('recommended'); 
            $table->integer('duration'); 
            $table->string('period',7); 
            
            $table->double('min_sub_discount', 9, 2); 

            $table->decimal('discount_percent',9, 2);  
            $table->double('daily_rates', 9, 2); 

            $table->double('plan_amount', 9, 2);  
            
            $table->integer('modified_by'); // user constrain
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
        Schema::dropIfExists('plans');
    }
}
