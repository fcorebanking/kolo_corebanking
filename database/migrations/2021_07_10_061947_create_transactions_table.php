<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use phpDocumentor\Reflection\Types\Nullable;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('account_id')->nullable(); // initiator user wb_account_details table           
            $table->string('transaction_reference');  //TXN random id
            $table->double('balance_before', 15, 2); //user balance before transaction
            $table->double('amount', 15, 2); //transaction amount
            $table->double('balance_after', 15, 2)->nullable(); //user balance after transaction
            $table->double('sms_charge', 5, 2)->nullable();
            $table->double('withdrawal_charge', 5, 2)->nullable();
            $table->string('description')->nullable();
            $table->string('system_description')->nullable();  //TXN random id
            $table->unsignedBigInteger('branch_id')->nullable(); // initiator user wb_account_details table           
            $table->unsignedBigInteger('parent_transaction_id')->nullable();
            $table->unsignedBigInteger('loan_id')->nullable();  // initiator user loans table           
            $table->unsignedBigInteger('transaction_status_id')->default(1)->comment('1=active,0=reverse');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->foreign('company_id')->references('id')->on('company')->nullOnDelete();

            $table->double('company_balance_before', 15, 2)->nullable(); //user balance before transaction
            $table->double('company_amount', 15, 2)->nullable(); //transaction amount
            $table->double('company_balance_after', 15, 2)->nullable(); //user balance after transaction

            $table->unsignedBigInteger('transaction_type_id'); //constrain to transaction_type table
            $table->foreign('transaction_type_id')->references('id')->on('transaction_types');

            $table->unsignedBigInteger('transaction_remarks_id'); //constrain to transaction_remarks table
            $table->foreign('transaction_remarks_id')->references('id')->on('transaction_remarks'); // derive from transaction_remarkstable

            $table->unsignedBigInteger('credit_debit_type_id'); //constrain to credit_debit_types table
            // $table->foreign('credit_debit_type_id')->references('id')->on('credit_debit_types'); // derive from transaction_remarkstable

            $table->unsignedBigInteger('created_by');  //constrain from user table
            $table->foreign('created_by')->references('id')->on('users');

            $table->unsignedBigInteger('modified_by')->nullable();  //constrain from user table 
            $table->foreign('modified_by')->references('id')->on('users');
            $table->timestamp('created_date')->nullable();
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
        Schema::dropIfExists('transactions');
    }
}
