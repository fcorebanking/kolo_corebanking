<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKycLevelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kyc_levels', function (Blueprint $table) {
            $table->id();
            $table->float('daily_transaction_limit', 15, 2);
            $table->float('maximum_balance', 15, 2);
            $table->double("minimum_transfer", 15, 2)->default(0);
            $table->double("maximum_transfer", 15, 2)->default(0);
            $table->double("transfer_charge", 15, 2)->default(0);
            $table->double("daily_transaction_count", 15, 2)->default(0);
            $table->string('requirements');
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
        Schema::dropIfExists('kyc_levels');
    }
}
