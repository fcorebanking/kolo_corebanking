<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loan_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_product_id');// derive from loan_product_id table
            $table->integer('duration'); 
            $table->unsignedBigInteger('period_id');// derive from loan_periods table
            $table->double('principal', 9, 2); 
            $table->double('loan_balance', 9, 2); 
            $table->date('start_at'); 
            $table->date('grace_at');           
            $table->double('repayment', 9, 2); 
            $table->integer('created_by');  //user table
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
        Schema::dropIfExists('loan_groups');
    }
}
