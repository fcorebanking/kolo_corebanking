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
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('account_id');
            $table->index('id');
            $table->index('transaction_reference');
            $table->index('transaction_type_id');
            $table->index('transaction_remarks_id');
            $table->index('created_by');
            $table->index('parent_transaction_id');
            $table->index('transaction_status_id');
            $table->index('company_id');
            $table->index('modified_by');
            // $table->index('loan_repayment_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            //
        });
    }
};
