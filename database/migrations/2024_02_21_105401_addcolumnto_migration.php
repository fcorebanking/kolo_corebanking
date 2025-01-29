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
        Schema::table('closings_balances', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('closings_balances', function (Blueprint $table) {
            //
        });
    }
};
