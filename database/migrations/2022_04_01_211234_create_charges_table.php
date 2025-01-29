<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_charges', function (Blueprint $table) {
            $table->id();
            $table->double("bank_transfer_charge");
            $table->double("cable_tv_charge");
            $table->double("electricity_charge");
            $table->double("ussd_charge");
            $table->double("whatsapp_charge");
            $table->double("sms_charge");
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
        Schema::dropIfExists('charges');
    }
}
