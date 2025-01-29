<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Sublog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sublogs', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id'); //constrain from company table
            $table->unsignedBigInteger('transaction_id'); //Constrain Transaction Table
            $table->unsignedBigInteger('plan_id'); //Constrain Transaction Table
            $table->integer('quantity'); 
            $table->date('start_date'); //subscription start
            $table->date('end_date'); //subscription ends
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
        Schema::dropIfExists('sublogs');
    }
}
