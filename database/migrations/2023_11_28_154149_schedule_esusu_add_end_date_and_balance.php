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
        Schema::table('schedule_esusus', function (Blueprint $table) {
            $table->decimal('wallet')->default(0);
            $table->date('end_date');
            $table->unsignedBigInteger('wb_wallet_id');
            $table->foreign('wb_wallet_id')->references('id')->on('wb_account_details');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
