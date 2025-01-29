<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsapisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('smsapis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('company'); 
            $table->string('name',100); 
            $table->string('phone_variable',20); 
            $table->string('message_variable',20); 
            $table->string('api_url',300);           
            $table->string('sender_variable',30);           
            $table->text('content_type')->nullable(); 
            $table->text('authorization')->nullable();
            $table->integer('access_setup')->default(0)->comment("1=all can use,0=withdin company")->nullable();
            $table->unsignedBigInteger('request_method_id');
            $table->double('smscharge',9, 5)->nullable(); 
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
        Schema::dropIfExists('smsapis');
    }
}
