<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('savingstransactionlogs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->uniqid();
            $table->unsignedBigInteger('customersaving_id');
            $table->foreign('customersaving_id')->references('id')->on('customersavings');
            $table->decimal('amount',9,2);
            $table->string('reference');
            $table->string('gateway_response');
            $table->string('paid_at');
            $table->string('createdAt');
            $table->string('channel');
            $table->string('currency');
            $table->string('ip_address');
            $table->string('metadata');
            $table->json('log');
            $table->string('email');
            $table->string('customer_code');
            $table->json('authorization');
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
        Schema::dropIfExists('savingstransactionlogs');
    }
};
