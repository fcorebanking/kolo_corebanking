<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TransactionLoanedit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // $table->dropColumn(['loan_id']);
            // $table->dropColumn(['loan_repayment_id']);
            // $table->unsignedBigInteger('loan_repayment_id')->nullable();  // initiator loan table           
            // $table->foreign('loan_repayment_id')->references('id')->on('loan_repayments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
