<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    { 
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique(); // 
            $table->integer('user_id'); // constrain user table
            $table->unsignedBigInteger('loan_product_id'); // derive from loan products table
            $table->integer('duration'); 
            $table->string('DurationPeriod'); // 
            $table->unsignedBigInteger('loan_status_id')->default(3); // derive from loan_status table
            $table->unsignedBigInteger('period_id'); // derive from loan_periods table
            $table->unsignedBigInteger('loan_group_id')->nullable(); // derive from loan_groups table
            $table->double('rate', 9, 2);  
            $table->double('principal', 15, 2);  
            $table->double('loanBalance', 15, 2);  
            $table->date('start_at')->nullable(); 
            $table->date('grace_at')->nullable();
            $table->integer('LoanBranch_id'); 
            $table->integer('LoanOfficer_id');  //user table
            $table->integer('LoanType'); 
            $table->integer('disbursementCash')->nullable(); 
            $table->double('LoanFee',15,2);  //
            $table->integer('LoanFeeMethod');  //
            $table->double('mrgtFee',15,2);  //
            $table->integer('mrgtFeeMethod');  //
            $table->integer('auto_pay'); //
            $table->integer('on_guarantor'); //
            $table->integer('keep_as_past_onMissed_onExtend'); //
            $table->integer('ExtendDuration'); //
            $table->integer('missedFee'); //
            $table->text('remark')->nullable(); //
            $table->integer('created_by');  //user table
            $table->integer('modified_by')->nullable();  //user table
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
        Schema::dropIfExists('loans');
    }
}
