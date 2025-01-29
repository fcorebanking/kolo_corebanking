<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyValutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_valuts', function (Blueprint $table) {
            $table->id(); 
            $table->unsignedBigInteger('company_id');
            $table->string('accountno')->nullable();          
            $table->string('vault');            
            $table->double('value', 15, 8); 
            $table->integer('deleteable'); 
            $table->integer('created_by'); 
            
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
        Schema::dropIfExists('company_valuts');
    }
}
