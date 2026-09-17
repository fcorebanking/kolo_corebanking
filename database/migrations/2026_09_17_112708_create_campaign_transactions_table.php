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
        Schema::create('campaign_transactions', function (Blueprint $table) {
            $table->id();

            /*
             * Campaign being affected.
             */
            $table->foreignId('campaign_id')
                ->constrained('campaigns')
                ->cascadeOnDelete();

            /*
             * User who initiated the transaction.
             *
             * For deposits:
             *     contributor
             *
             * For withdrawals:
             *     campaign creator
             */
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /*
             * Company associated with the Campaign.
             */
            $table->foreignId('company_id')
                ->constrained('company')
                ->cascadeOnDelete();

            /*
             * Transaction types:
             *
             * deposit
             * withdrawal
             * withdrawal_fee
             * reversal
             */
            $table->string('type', 30);

            /*
             * Amount involved in the Campaign transaction.
             */
            $table->decimal('amount', 15, 2);

            /*
             * Fee associated with the transaction.
             *
             * Normally zero for deposits.
             */
            $table->decimal('fee', 15, 2)
                ->default(0);

            /*
             * Campaign balance immediately before the transaction.
             */
            $table->decimal('balance_before', 15, 2);

            /*
             * Campaign balance immediately after the transaction.
             */
            $table->decimal('balance_after', 15, 2);

            /*
             * Internal Campaign transaction reference.
             */
            $table->string('transaction_reference')
                ->unique();

            /*
             * successful / failed / reversed etc.
             */
            $table->string('status', 30)
                ->default('successful');

            /*
             * Human-readable description.
             */
            $table->text('description')
                ->nullable();

            /*
             * Additional structured information.
             */
            $table->json('metadata')
                ->nullable();

            $table->timestamps();

            /*
             * Useful reporting indexes.
             */
            $table->index([
                'campaign_id',
                'type',
            ]);

            $table->index([
                'company_id',
                'type',
            ]);

            $table->index([
                'user_id',
                'created_at',
            ]);

            $table->index([
                'campaign_id',
                'created_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_transactions');
    }
};