<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffPayRollsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staff_pay_rolls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("staff_id");
            $table->foreign('staff_id')->references('id')->on('users');
            $table->unsignedBigInteger("employment_type_id");
            $table->foreign('employment_type_id')->references('id')->on('employment_types');
            $table->float('basic_salary');
            $table->integer('day_of_payment');
            $table->float('house_allowance');
            $table->float('medical_allowance');
            $table->float('special_allowance');
            $table->float('overtime_allowance');
            $table->float('transport_allowance');
            $table->float('paid_leave');
            $table->float('pension_deduction');
            $table->float('provident_fund_allowance');
            $table->float('unpaid_leave_deduction');
            $table->float('loan_deduction');
            $table->float('other_allowances');
            $table->float('tax_deductions');
            $table->float('provident_fund_deduction');
            $table->float('other_deductions');
            $table->float('gross_salary');
            $table->float('total_deduction');
            $table->integer('payment_method')->comment("0 - Wallet Transfer, 1 - Bank Transfer");
            $table->string('bank_id')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->string('wallet_account_number')->nullable();
            $table->string('wallet_account_name')->nullable();
            $table->string('payment_mode')->default(0)->comment("0 - pay automatically, 1 - pay manually");
            $table->float('net_salary');
            $table->integer('salary_payment_status')->comment("0 - Not Paid, 1 - Paid");
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
        Schema::dropIfExists('staff_pay_rolls');
    }
}
