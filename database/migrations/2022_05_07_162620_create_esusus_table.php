<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEsususTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esusus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('esusu_type_id'); // derive from esusu_types table
            $table->foreign('esusu_type_id')->references('id')->on('esusu_types'); 
            $table->unsignedBigInteger('wb_account_detail_id'); // initiator user wb_account_details table   
            $table->double('rate',9,2);
            $table->date('start_date');
            $table->date('commence_date');
          
            $table->double('withdrawable',9,2);
            $table->double('accum_charge',9,2);
            $table->double('wallet',9,2);
            $table->double('overdraft',9,2);
            $table->unsignedBigInteger('missed_day');
            $table->unsignedBigInteger('status_id');
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
        Schema::dropIfExists('esusus');
    }
}
