<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StaffpermissionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staffpermission', function (Blueprint $table) {
            $table->id();          
                              
            $table->unsignedBigInteger('staffrole_id');
            $table->foreign('staffrole_id')->references('id')->on('staffrole');           
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');                      
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users');   
            $table->unsignedBigInteger('modified_by')->nullable();// user table constrain
            $table->foreign('modified_by')->references('id')->on('users');  
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
        Schema::dropIfExists('staffpermission');
        Schema::enableForeignKeyConstraints();
    }
}
