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
        Schema::table('wb_account_details', function (Blueprint $table) {
            $table->index('id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->index('id');
            $table->index('company_id');
            $table->index('status_id');
            $table->index('branch_id');
            $table->index('account_officer_id');
            $table->index('user_type_id');
            $table->index('bank_id');
            $table->index('bvn_status_id');

        });
        Schema::table('credit_debit_types', function (Blueprint $table) {
            $table->index('id');
        });
        Schema::table('loans', function (Blueprint $table) {
            $table->index('id');
            $table->index('ref');
            $table->index('user_id');
            $table->index('loan_product_id');
            $table->index('loan_status_id');
            $table->index('period_id');
            $table->index('loan_group_id');
        });

        Schema::table('branch', function (Blueprint $table) {
            $table->index('id');
            $table->index('company_id');
            $table->index('created_by');
            $table->index('modified_by');
            $table->index('status_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('other', function (Blueprint $table) {
            //
        });
    }
};
