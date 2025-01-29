<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NextOfKin extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('next_of_kin', function (Blueprint $table) {
            $table->id();
        $table->string('next_of_kin_fname',100);  // 
        $table->string('next_of_kin_lname',100);  // 
        $table->string('next_of_kin_phone',15);  // 
        $table->string('next_of_kin_address');  // user account type ID if it is personal or Company account (constrain from account_type table)
        $table->unsignedBigInteger('next_of_kin_relationship_status_id');   
    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
