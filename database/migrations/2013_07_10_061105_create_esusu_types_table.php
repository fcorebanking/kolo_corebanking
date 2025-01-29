<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEsusuTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esusu_types', function (Blueprint $table) {
            $table->id();
            $table->string('esusu_type'); 
            $table->double('min_rate',9,2); 
            $table->double('max_rate',9,2); 
            $table->integer('circle_charge');//in percent 
            $table->integer('duration'); 
            $table->double('overdraft_charge',9,2); 
            $table->unsignedBigInteger('company_id')->index('company_id');

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
        Schema::dropIfExists('esusu_types');
    }
}
