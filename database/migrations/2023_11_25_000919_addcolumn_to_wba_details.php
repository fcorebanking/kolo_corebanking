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
        Schema::table('wb_account_details', function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')->nullable();
        });
        Schema::table('branch', function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
};
