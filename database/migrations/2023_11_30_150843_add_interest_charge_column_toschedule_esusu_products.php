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
        Schema::table('schedule_esusu_products', function (Blueprint $table) {
            $table->decimal('circle_out_charge')->default(0);
            $table->decimal('withdraw_out_charge')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */

};
