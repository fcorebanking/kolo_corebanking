<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCryptoTransferTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crypto_transfer_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->double('Sent_amount',15,8);
            $table->double('dollar_amount',15,8);
            $table->double('Receiver_amount',15,8);
            $table->string('sender_coin_code');
            $table->string('receiver_coin_code');
            $table->string('txnID');
            $table->integer('txntype');
            $table->integer('status');
            $table->string('Description');
            $table->double('sender_bal_before',15,8);
            $table->double('sender_bal_after',15,8);
            $table->double('receiver_bal_before',15,8);
            $table->double('receiver_bal_after',15,8);
            $table->unsignedBigInteger('sender_wallet_ID')->nullable(); //coinremitter_wallets
            $table->foreign('sender_wallet_ID')->references('id')->on('coinremitter_wallets');
            $table->unsignedBigInteger('receiver_wallet_ID')->nullable();
            $table->foreign('receiver_wallet_ID')->references('id')->on('coinremitter_wallets');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->foreign('transaction_id')->references('id')->on('transactions');
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
        Schema::dropIfExists('crypto_transfer_transactions');
    }
}
