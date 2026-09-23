<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajo_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ajo_id')
    ->nullable()
    ->constrained('ajos')
    ->cascadeOnDelete();

            $table->foreignId('ajo_member_id')
                ->nullable()
                ->constrained('ajo_members')
                ->nullOnDelete();

            $table->foreignId('ajo_collection_cycle_id')
                ->nullable()
                ->constrained('ajo_collection_cycles')
                ->nullOnDelete();

            // User responsible for / affected by the transaction.
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('company_id');

            /*
             * Personal Ajo Account references.
             *
             * For example:
             * - deposit: destination_ajo_account_id
             * - withdrawal: source_ajo_account_id
             * - early leave penalty: destination_ajo_account_id = creator account
             * - maturity return: destination_ajo_account_id = member account
             */
            $table->foreignId('source_ajo_account_id')
                ->nullable()
                ->constrained('ajo_accounts')
                ->nullOnDelete();

            $table->foreignId('destination_ajo_account_id')
                ->nullable()
                ->constrained('ajo_accounts')
                ->nullOnDelete();

            /*
             * Main Finova wallet account involved in a Main <-> Ajo Account move.
             * This is the existing wb_account_details row.
             */
            $table->unsignedInteger('main_account_id')->nullable();

            /*
             * Links back to existing Finova accounting records when applicable.
             * We do not create hard FKs because these are legacy tables whose
             * primary-key types may vary in the existing installation.
             */
            $table->unsignedBigInteger('finova_transaction_id')->nullable();
            $table->unsignedBigInteger('company_financial_entry_id')->nullable();

            /*
             * Ajo transaction types include:
             * deposit
             * withdrawal
             * creation_fee
             * scheduled_collection
             * insufficient_balance_fee
             * maturity_fee
             * maturity_return
             * early_leave_penalty
             * early_leave_return
             * reversal
             */
            $table->string('type', 50);

            /*
             * Gross movement represented by this ledger row.
             */
            $table->decimal('amount', 20, 2)->default(0);

            /*
             * Total fee represented by this row.
             */
            $table->decimal('fee', 20, 2)->default(0);

            /*
             * Fee split tracking.
             *
             * Shared fees:
             *   company = 40%
             *   creator = 60%
             *
             * Creation fee:
             *   company = 100%
             *   creator = 0%
             */
            $table->decimal('company_fee_share', 20, 2)->default(0);
            $table->decimal('creator_fee_share', 20, 2)->default(0);

            /*
             * Source/destination balances make each movement auditable.
             */
            $table->decimal('source_balance_before', 20, 2)->nullable();
            $table->decimal('source_balance_after', 20, 2)->nullable();
            $table->decimal('destination_balance_before', 20, 2)->nullable();
            $table->decimal('destination_balance_after', 20, 2)->nullable();

            $table->string('transaction_reference', 80)->unique();
            $table->string('status', 30)->default('successful');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['ajo_id', 'type']);
            $table->index(['user_id', 'type']);
            $table->index(['company_id', 'type']);
            $table->index(['ajo_member_id', 'type']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajo_transactions');
    }
};