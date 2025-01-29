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
        Schema::create('savingsubscriptionlogs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->uniqid();
            $table->unsignedBigInteger('customersaving_id');
            $table->foreign('customersaving_id')->references('id')->on('customersavings');
            $table->string('status');
            $table->string('subscription_code');
            $table->decimal('amount',9,2);
            $table->string('cron_expression');
            $table->string('next_payment_date');
            $table->string('createdAt');
            $table->string('planName');
            $table->string('plan_code');
            $table->string('interval');
            $table->string('authorization_code');
            $table->string('card_type');
            $table->string('bank');
            $table->string('bin');
            $table->string('last4');
            $table->string('exp_year');
            $table->string('exp_month');
            $table->string('account_name');
            $table->string('customer_code');
            $table->string('description')->nullable();
            $table->string('email');
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
        Schema::dropIfExists('savingsubscriptionlogs');
    }
};
