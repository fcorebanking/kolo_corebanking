<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWbaChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wba_charges', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->tinyInteger('charge_by')->comment("0 - Charge by amount, 1 - Charge by Percentage");
            $table->double('charge');
            $table->double('minimum_charge_amount');
            $table->double('maximum_charge_amount');
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
        Schema::dropIfExists('wba_charges');
    }
}
