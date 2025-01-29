<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTelleroptionToPendingSavings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pending_savings', function (Blueprint $table) {
            $table->unsignedBigInteger('teller_option_id')->nullable();
            $table->foreign("teller_option_id")->references("id")->on("teller_options");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pending_savings', function (Blueprint $table) {
            //
        });
    }
}
