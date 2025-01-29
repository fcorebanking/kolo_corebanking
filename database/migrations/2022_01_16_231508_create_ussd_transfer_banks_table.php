<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUssdTransferBanksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ussd_transfer_banks', function (Blueprint $table) {
            $table->id();
            $table->string('bank_code', 191);
            $table->unsignedBigInteger('user_id')->notNull();
            $table->integer('options');
            $table->timestamps();
        });
        // Schema::table('ussd_transfer_banks', function(Blueprint $table){
        //     $table->foreign('bank_code')->references('bank_code')->on('banks')->nullOnDelete();
        //     $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ussd_transfer_banks');
    }
}
