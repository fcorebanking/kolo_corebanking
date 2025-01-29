<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoinremitterWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coinremitter_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');  //constrained to currencies type
            $table->foreign("user_id")->references("id")->on("users");
            $table->unsignedBigInteger('coinremitter_coin_id');  //constrained to currencies type
            $table->foreign("coinremitter_coin_id")->references("id")->on("coinremitter_coins");
            $table->text('address');
            $table->text('qr_code');
            $table->string('label');
            $table->dateTime('date_time');
            $table->double('balance',9,9);
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
        Schema::dropIfExists('coinremitter_wallets');
    }
}
