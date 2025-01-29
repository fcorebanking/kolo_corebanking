<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWbaCompanyChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wba_company_charges', function (Blueprint $table) {
            $table->id();
            $table->double("bank_transfer_charge");
            $table->double("incoming_transfer_charge_percentage",);
            $table->double("incoming_transfer_charge_capped_at");
            $table->double("cable_tv_charge");
            $table->double("electricity_charge");
            $table->double("ussd_charge");
            $table->double("whatsapp_charge");
            $table->double("sms_charge");
            $table->double("pos_charge_percent");
            $table->double("pos_charge_cap");
            $table->double("new_physical_card_charge");
            $table->double("physical_card_maintenance_charge");
            $table->double("crc_credit_bureau_charge")->default(0.0);
            $table->double("first_central_search_charge")->default(0.0);
            $table->double("eco_system_search_charge")->default(0.0);
            $table->double("karma_search_charge")->default(0.0);
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
        Schema::dropIfExists('wba_company_charges');
    }
}
