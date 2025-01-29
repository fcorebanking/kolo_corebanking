<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountMandatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_mandates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id')->nullable();  //constrained to currencies type         
            $table->foreign("wallet_id")->references("id")->on("user_wallets");
            $table->double('amount', 15, 4)->default(0); // 
            $table->double('amount_paid', 15, 4)->default(0); // 
            $table->double('amount_remaining', 15, 4)->default(0); // 
            $table->string('account_name'); // 
            $table->string('account_number'); // 
            $table->string('session_id')->unique('session_id'); // 
            $table->string('mandate_reference_number')->unique('mandate_reference_number'); // 
            $table->unsignedBigInteger('status_id')->nullable()->default(7);
            $table->foreign('status_id')->references('id')->on('statuses'); // user derive from status table
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
        Schema::dropIfExists('account_mandates');
    }
}
