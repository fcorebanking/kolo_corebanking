<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWovenWebhooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('woven_webhooks', function (Blueprint $table) {
            $table->id();

            $table->string("nuban")->nullable();
            $table->string("bank_code")->nullable();
            $table->string("account_name")->nullable();
            $table->string("account_email")->nullable();
            $table->string("account_mobile_number")->nullable();
            $table->integer("is_test")->nullable();
            $table->string("account_reference")->nullable();
            $table->string("source_bank_code")->nullable();
            $table->string("source_nuban")->nullable();
            $table->string("source_account_name")->nullable();
            $table->string("source_bvn")->nullable();
            $table->integer("amount")->nullable();
            $table->integer("fee")->nullable();
            $table->string("currency")->nullable();
            $table->string("transaction_time")->nullable();
            $table->string("nip_session_id")->nullable();
            $table->string("unique_reference")->nullable();
            $table->string("transaction_type")->nullable();
            $table->string("status")->nullable();
            $table->string("settled_status")->nullable();
            $table->integer("merchantId")->nullable();
            $table->integer("vnubanId")->nullable();
            $table->string("requestId")->nullable();
            $table->integer("amount_payable")->nullable();
            $table->string("hash")->nullable();

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
        Schema::dropIfExists('woven_webhooks');
    }
}
