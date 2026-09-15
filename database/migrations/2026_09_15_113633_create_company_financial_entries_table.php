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
        Schema::create('company_financial_entries', function (Blueprint $table) {
            $table->id();

            /*
             * Company that owns the financial entry.
             */
            $table->foreignId('company_id')
                ->constrained('company')
                ->cascadeOnDelete();

            /*
             * Optional customer associated with the entry.
             *
             * Example:
             * - Target Savings interest expense
             * - Target Savings early withdrawal revenue
             */
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
             * Optional Target Savings record associated
             * with the financial entry.
             */
            $table->foreignId('target_savings_id')
                ->nullable()
                ->constrained('target_savings')
                ->nullOnDelete();

            /*
             * Optional main Finova transaction associated
             * with this financial entry.
             */
            $table->foreignId('transaction_id')
                ->nullable()
                ->constrained('transactions')
                ->nullOnDelete();

            /*
             * revenue = money earned by the company
             * expense = money spent/owed by the company
             */
            $table->string('type', 20);

            /*
             * Example categories:
             *
             * target_savings_interest
             * target_savings_early_withdrawal_fee
             * etc.
             */
            $table->string('category');

            /*
             * Financial amount of the entry.
             */
            $table->decimal('amount', 15, 2);

            /*
             * Internal unique accounting reference.
             */
            $table->string('reference')->unique();

            /*
             * Human-readable explanation.
             */
            $table->text('description')->nullable();

            /*
             * Additional structured information.
             */
            $table->json('metadata')->nullable();

            /*
             * Successful, reversed, cancelled, etc.
             */
            $table->string('status', 30)->default('successful');

            $table->timestamps();

            /*
             * Useful indexes for P&L reporting.
             */
            $table->index([
                'company_id',
                'type',
            ]);

            $table->index([
                'company_id',
                'category',
            ]);

            $table->index([
                'company_id',
                'created_at',
            ]);

            $table->index([
                'target_savings_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_financial_entries');
    }
};