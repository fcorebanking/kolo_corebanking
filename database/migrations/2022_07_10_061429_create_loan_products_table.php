<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()

    {  //$table->foreign('bvn_status_id')->references('id')->on('statuses'); // check if user have input bvn ->index('phone'); 
        Schema::create('loan_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');//loan_interest_method
            $table->foreign('company_id')->references('id')->on('company')->onDelete('cascade');
            $table->string('product_name'); 
            $table->double('interest_rate', 9, 2);
            $table->double('minPrincipal', 15, 2)->default(0)->nullable();
            $table->double('DefaultPrincipal', 15, 2)->default(0)->nullable();
            $table->double('maxPrincipal', 15, 2)->default(0)->nullable();
            
            $table->unsignedBigInteger('loanFeeMethod')->default(0)->nullable();
            $table->double('loanFee', 15, 2)->nullable();
            $table->unsignedBigInteger('managementFeeMethod')->default(0)->nullable();
            $table->double('managementFee', 15, 2)->default(0)->nullable();
            $table->integer('minDuration')->default(0);
            $table->integer('DefaultDuration')->default(0);
            $table->integer('maxDuration')->default(0);
            $table->unsignedBigInteger('repayment_cycle_id')->nullable(); //loan_periods table when u should be paying repayment
            $table->foreign('repayment_cycle_id')->references('id')->on('loan_periods')->onDelete('cascade');
            $table->unsignedBigInteger('approximate_status')->default(0)->comment('0=down,1=up'); 
            $table->unsignedBigInteger('auto_pay')->default(0)->comment('0=no,1=yes'); 
            $table->integer('minExtendDuration')->default(0);
            $table->integer('DefaultExtendDuration')->default(0);
            $table->integer('maxExtendDuration')->default(0);
            $table->double('missedFee', 15, 2)->default(0);
            $table->unsignedBigInteger('keep_as_past_onMissed_onExtend')->default(0)->comment('0=no,1=yes'); 
            $table->integer('status_id')->default(1)->comment('1=Active,2=Disabled,3=Disable loans');
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
        Schema::dropIfExists('loan_products');
    }
}
