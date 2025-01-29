<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoinremitterCoinInvoicesTable extends Migration
{
    public function up()
    {
        Schema::create('coinremitter_coin_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');  //constrained to currencies type
            $table->foreign("user_id")->references("id")->on("users");
            $table->unsignedBigInteger('coinremitter_wallet_id');  //constrained to currencies type
            $table->foreign("coinremitter_wallet_id")->references("id")->on("coinremitter_wallets");
            $table->string('rond_id');
            $table->string('invoice_id');
            $table->string('merchant_id');
            $table->string('url');
            $table->double('amountInCoin',9,2);
            $table->double('amountInUSD',9,2);
            $table->string('base_currency');
            $table->string('coin');
            $table->string('wallet_name');
            $table->string('address');
            $table->string('status');
            $table->integer('status_code');
            $table->string('expire_on');
            $table->string('invoice_date');
            $table->text('conversion_rate');
            $table->text('description');
            $table->string('last_updated_date');
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('coinremitter_coin_invoices');
    }
}
