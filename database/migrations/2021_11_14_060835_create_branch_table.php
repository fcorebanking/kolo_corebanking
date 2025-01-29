<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBranchTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('branch', function (Blueprint $table) {
            $table->id();          
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('company');        
            $table->unsignedBigInteger('branch_manager')->nullable();
            $table->foreign('branch_manager')->references('id')->on('users');       
            $table->string('branch_name');//           
            $table->string('address');// 
            $table->unsignedBigInteger('status_id');
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
        Schema::dropIfExists('branch');
    }
}
