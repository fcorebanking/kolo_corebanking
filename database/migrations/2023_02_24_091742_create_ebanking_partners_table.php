<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEbankingPartnersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ebanking_partners', function (Blueprint $table) {
            $table->id();
            $table->string('ebanking_service');
            $table->tinyInteger('account_verification_enabled')->default(0)->comment("0 - Not Enabled, 1 - Enabled");
            $table->tinyInteger('bank_transfer_enabled')->default(0)->comment("0 - Not Enabled, 1 - Enabled");
            $table->tinyInteger('airtime_data_enabled')->default(0)->comment("0 - Not Enabled, 1 - Enabled");
            $table->tinyInteger('cable_tv_enabled')->default(0)->comment("0 - Not Enabled, 1 - Enabled");
            $table->tinyInteger('electricity_enabled')->default(0)->comment("0 - Not Enabled, 1 - Enabled");
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
        Schema::dropIfExists('ebanking_partners');
    }
}
