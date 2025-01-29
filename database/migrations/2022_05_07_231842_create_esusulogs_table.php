<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEsusulogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esusulogs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('esusu_id'); // initiator user wb_account_details table   
            $table->double('rate',9,2);
            $table->date('commence_date');
            $table->unsignedBigInteger('prev_card');
            $table->unsignedBigInteger('today_card');
            $table->double('today_cash',9,2);
            $table->unsignedBigInteger('circle_out');
            $table->double('withdrawable',9,2);
            $table->double('wallet',9,2);
            $table->double('overdraft',9,2);
            $table->unsignedBigInteger('missed_day');
            
            $table->double('circle_out_charge',9,2);
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('txnid')->nullable();
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
        Schema::dropIfExists('esusulogs');
    }
}
