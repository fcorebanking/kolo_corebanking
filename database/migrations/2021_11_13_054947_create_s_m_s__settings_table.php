<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSMSSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::create('s_m_s__settings', function (Blueprint $table) {
        //     $table->id();
        //     // $table->unsignedBigInteger('company_id')->nullable();
        //     // $table->foreign('company_id')->references('id')->on('company'); 
        //     // $table->string('name',100); 
        //     // $table->string('smsapi');         
        //     // $table->string('sms_phone',15); 
        //     // $table->string('sms_message',15); 
        //     $table->timestamps();
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('s_m_s__settings');
    }
}
