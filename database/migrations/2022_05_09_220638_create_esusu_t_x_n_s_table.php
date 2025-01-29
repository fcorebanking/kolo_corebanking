<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEsusuTXNSTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    { 
        Schema::create('esusu_t_x_n_s', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('esusu_id'); // derive from esusu_types table
            $table->foreign('esusu_id')->references('id')->on('esusus'); 
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->foreign('transaction_id')->references('id')->on('transactions'); 
            $table->integer('no_card');
            $table->date('created_date'); 
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
        Schema::dropIfExists('esusu_t_x_n_s');
    }
}
