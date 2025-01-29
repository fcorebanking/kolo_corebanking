<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappBotLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('whatsapp_bot_log', function (Blueprint $table) {
            $table->id();
            $table->string("status")->nullable();
            $table->dateTime("time")->nullable();
            $table->string("session_operation")->nullable();
            $table->string("session_phone")->nullable();
            $table->string("vtu_phone")->nullable();
            $table->double("vtu_amount")->nullable();
            $table->string("vtu_network")->nullable();
            $table->integer('vtu_type')->nullable();
            $table->string("account_name")->nullable();
            $table->string('transaction_type')->nullable();
            $table->string("api_charge")->nullable();
            $table->integer('new_pin')->nullable();
            $table->string('uniqueid')->nullable();
            $table->string('company_transaction_ref')->nullable();

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
        Schema::dropIfExists('whatsapp_bot_log');
    }
}
