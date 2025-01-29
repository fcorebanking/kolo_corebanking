<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanRepaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');  // initiator loan table           
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            $table->double('amount',15,5); 
            $table->double('loanFee',15,5)->default(0); 
            $table->double('mgrtFee',15,5)->default(0); 
            $table->date('paymentDate'); 
            $table->unsignedBigInteger('branch_id');  // initiator branch table           
            $table->foreign('branch_id')->references('id')->on('branch')->onDelete('cascade');
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
        Schema::dropIfExists('loan_repayments');
    }
}
