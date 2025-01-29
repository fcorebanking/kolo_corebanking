<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransferLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transfer_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id'); // initiator user wb_account_details table           
            
            $table->string('acct_name'); // benefeciary name
            $table->string('acct_no'); // benefeciary accountno
            $table->string('bank'); // benefeciary bank
            $table->unsignedBigInteger('transaction_id');  // transaction ID constrain to transactions table 
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
        Schema::dropIfExists('transfer_logs');
    }
}
