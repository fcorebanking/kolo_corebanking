<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerRestrictionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_restrictions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');   // initiator users table 
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('customerpermission_id');   
            $table->foreign('customerpermission_id')->references('id')->on('customer_permissions');       
            $table->string('remark');
            $table->unsignedBigInteger('created_by');  
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
        Schema::dropIfExists('customer_restrictions');
    }
}
