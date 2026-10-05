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
        Schema::create('target_savings_transactions', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('target_savings_id');
    $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('company_id');

    $table->string('type');

    $table->decimal('amount', 18, 2)->default(0);
    $table->decimal('fee', 18, 2)->default(0);

    $table->decimal('balance_before', 18, 2)->default(0);
    $table->decimal('balance_after', 18, 2)->default(0);

    /*
     * For interest entries this stores the calendar day
     * that the interest represents.
     */
    $table->date('interest_date')->nullable();

    $table->string('transaction_reference')->unique();

    $table->string('status')->default('successful');
    $table->text('description')->nullable();

    $table->json('metadata')->nullable();

    $table->timestamps();

    $table->index('target_savings_id');
    $table->index(['target_savings_id', 'type']);
    $table->index(['company_id', 'type']);

    /*
     * Prevent the same target from receiving
     * interest twice for the same day.
     */
    $table->unique(
        ['target_savings_id', 'type', 'interest_date'],
        'target_savings_daily_interest_unique'
    );
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target_savings_transactions');
    }
};
