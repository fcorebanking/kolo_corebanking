<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBeneficiariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->foreign("bank_id")->references("id")->on("banks");
            $table->string('accountNo')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->foreign("transaction_id")->references("id")->on("transactions");
            $table->unsignedBigInteger('pending_saving_id')->nullable();
            $table->foreign("pending_saving_id")->references("id")->on("pending_savings");
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign("user_id")->references("id")->on("users");
            $table->unsignedBigInteger('account_id')->nullable();
            $table->foreign("account_id")->references("id")->on("wb_account_details");
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
        Schema::dropIfExists('beneficiaries');
    }
}
