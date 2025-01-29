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
        Schema::create('schedule_esusu_repayments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('company');
            $table->unsignedBigInteger('schedule_esusu_product_id');
            $table->foreign('schedule_esusu_product_id')->references('id')->on('schedule_esusu_products');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('schedule_esusu_id');
            $table->foreign('schedule_esusu_id')->references('id')->on('schedule_esusus');
            $table->decimal('amount',9,2);
            $table->string('type');
            $table->date('started_debiting_at');
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
        Schema::dropIfExists('schedule_esusu_repayments');
    }
};
