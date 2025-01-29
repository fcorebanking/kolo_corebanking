<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('import_loans', function (Blueprint $table) {
            $table->id();
            $table->string('loanref')->nullable();
            $table->string('customer')->nullable()->comment('customer uuid');//uuid
            $table->string('branch')->nullable();
            $table->string('officer')->nullable()->comment('officer uuid');
            $table->string('loan_products')->nullable();
            $table->string('loan_type')->nullable();
            $table->string('principal')->nullable();
            $table->string('Duration')->nullable();
            $table->string('DurationPeriod')->nullable()->comment('weeks months');
            $table->string('period')->nullable();
            $table->string('RepaymentAmt')->nullable();
            $table->string('loanFeeMethod')->nullable();
            $table->string('loanFee')->nullable();
            $table->string('managementFee')->nullable();
            $table->string('managementFeeMethod')->nullable();
            $table->string('keep_as_past_onMissed_onExtend')->nullable();
            $table->string('ExtendDuration')->nullable();
            $table->string('missedFee')->nullable();
            $table->string('guarantorOption')->nullable();
            $table->string('auto_pay')->nullable();
            $table->string('remark')->nullable();
            $table->string('status')->nullable();
            $table->string('paid')->nullable();

            $table->unsignedBigInteger('company_id')->nullable();
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
        Schema::dropIfExists('import_loans');
    }
};
