<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_wallets', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->double('balance', 15, 8);
            $table->integer('sms');
            $table->integer('ussd');

            $table->float('whatsapp');
            $table->float('whatsapp_charge');
            $table->string('vaccount');
            $table->string('refaccount');
            $table->unsignedBigInteger('bank_id');
            $table->foreign('bank_id')->references('id')->on('banks');

            $table->unsignedBigInteger('sms_type_id');
            $table->float('withdraw_charge');
            $table->float('sms_charge');
            $table->float('ussd_charge');
            $table->tinyInteger('bank_replaced')->default(0)->comment("0 - bank has not been replaced, 1 - bank has been replaced");
            $table->unsignedBigInteger('modified_by');
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
        Schema::dropIfExists('company_wallets');
    }
}
