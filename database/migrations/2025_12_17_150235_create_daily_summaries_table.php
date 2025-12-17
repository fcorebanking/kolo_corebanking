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
        Schema::create('daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedInteger('transaction_type_id');
            $table->decimal('amount', 18, 2);
            $table->date('summary_date');
            $table->timestamps();

            // 🔑 Indexes for performance
            $table->index(['company_id', 'summary_date'], 'idx_company_date');

            // 🔥 Recommended composite index for dashboards
            $table->index(
                ['company_id', 'summary_date', 'transaction_type_id'],
                'idx_company_date_type'
            );

            // 🚫 Prevent duplicate daily rows
            $table->unique(
                ['company_id', 'branch_id', 'account_id', 'transaction_type_id', 'summary_date'],
                'uniq_daily_profit'
            );

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('daily_summaries');
    }
};
