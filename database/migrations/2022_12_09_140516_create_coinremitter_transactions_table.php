<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoinremitterTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coinremitter_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');  //constrained to currencies type
            $table->foreign("user_id")->references("id")->on("users");
            $table->string('with_id');
            $table->string('txid');
            $table->string('status_id');
            $table->text('explorer_url');
            $table->double('amount',9,2);
            $table->double('transaction_fees',9,9);
            $table->double('processing_fees',9,9);
            $table->double('total_amount',9,9);
            $table->text('to_address');
            $table->text('wallet_id');
            $table->text('wallet_name');
            $table->text('coin_short_name');
            $table->text('txn_date');
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
        Schema::dropIfExists('coinremitter_transactions');
    }
}
