<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Staffrole extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staffrole', function (Blueprint $table) {
        $table->id();    
        $table->string('name',20);
        $table->unsignedBigInteger('company_id');
        $table->foreign('company_id')->references('id')->on('company');     
        $table->unsignedBigInteger('created_by');
        $table->foreign('created_by')->references('id')->on('users');    
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
        Schema::dropIfExists('staffrole');
        Schema::enableForeignKeyConstraints();
    }
}
