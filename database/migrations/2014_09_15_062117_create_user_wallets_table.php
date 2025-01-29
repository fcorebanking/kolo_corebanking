<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_wallets', function (Blueprint $table) {
            $table->id();
            $table->double('balance', 15, 4)->default(0); // 
            $table->double('ledger_balance', 15, 4); // 
            $table->double('lien_amount', 15, 4)->default(0); // 
            $table->double('lien_deducted', 15, 4)->default(0); // 
            $table->double('lien_remaining', 15, 4)->default(0); // 
            $table->unsignedBigInteger('wb_account_detail_id');  // initiator user wb_account_details table           
            $table->foreign('wb_account_detail_id')->references('id')->on('wb_account_details')->onDelete('cascade')->change();
            $table->integer('has_lien')->comment("0 - No Lien, 1 - Lien applied");
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
        Schema::dropIfExists('user_wallets');
    }
}
