<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fixed_savings', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('company_id');

    // Wallet from which the customer deposits.
    $table->unsignedBigInteger('source_account_id');

    // Wallet to which money is released.
    $table->unsignedBigInteger('destination_account_id');

    $table->string('fixed_name');
    $table->decimal('fixed_amount', 18, 2);

    $table->decimal('current_balance', 18, 2)->default(0);
    $table->decimal('total_deposited', 18, 2)->default(0);
    $table->decimal('total_interest_earned', 18, 2)->default(0);

    // Snapshot the terms at creation time.
    $table->decimal('interest_rate', 10, 4)->default(0);
    $table->decimal('early_withdrawal_fee_percentage', 10, 4)->default(0);

    $table->date('withdrawal_date');

    /*
     * Latest date for which interest has successfully been posted.
     * NULL means no interest has been posted yet.
     */
    $table->date('last_interest_date')->nullable();

    $table->timestamp('matured_at')->nullable();
    $table->timestamp('withdrawn_at')->nullable();

    $table->string('status')->default('active');

    $table->timestamps();

    $table->index(['company_id', 'status']);
    $table->index(['user_id', 'status']);
    $table->index(['withdrawal_date', 'status']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_savings');
    }
};
