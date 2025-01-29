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
        Schema::create('customersavings', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->uniqid();
            $table->string('name');
            $table->decimal('rate',9,2);
            $table->decimal('balance',9,2)->default(0);
            $table->decimal('target',9,2);
            $table->integer('duration');
            $table->string('frequency');
            $table->string('status')->comment('active,in-active');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('next_pay_date')->nullable();
            $table->string('payment_method');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users');
            $table->string('plan_code')->nullable();
            $table->string('integration')->nullable();
            $table->string('paystack_id')->nullable();
            $table->string('authorization_url')->nullable();
            $table->string('access_code')->nullable();
            $table->string('reference')->nullable();
            $table->string('customer_code')->nullable();
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
        Schema::dropIfExists('customersavings');
    }
};
