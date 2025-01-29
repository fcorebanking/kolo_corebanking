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
        Schema::create('pendingcustomersavings', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->uniqid();
            $table->string('status')->comment('complete,pending');
            $table->string('method')->comment('paystak,wallet');
            $table->decimal('amount',9,2);
            $table->date('paymentDate');
            $table->string('paid_at')->nullable();
            $table->unsignedBigInteger('customersaving_id');
            $table->foreign('customersaving_id')->references('id')->on('customersavings');
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
        Schema::dropIfExists('pendingcustomersavings');
    }
};
