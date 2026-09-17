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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();

            /*
             * Campaign creator.
             */
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /*
             * Company behind the creator.
             */
            $table->foreignId('company_id')
                ->constrained('company')
                ->cascadeOnDelete();

            /*
             * Internal Finova account ID belonging to the creator.
             *
             * The account number is also stored below because it is
             * the account number supplied during Campaign creation.
             */
            $table->unsignedBigInteger('creator_account_id')
                ->nullable()
                ->index();

            /*
             * Creator's destination account number.
             *
             * Stored as a string because account numbers should never
             * be treated as numeric values.
             */
            $table->string('creator_account_number', 30);

            /*
             * Campaign information.
             */
            $table->string('campaign_name', 255);

            $table->text('description')
                ->nullable();

            /*
             * Benchmark / target.
             *
             * This does NOT limit the amount contributors can send.
             */
            $table->decimal('target_amount', 15, 2)
                ->default(0);

            /*
             * Lifetime amount contributed to this Campaign.
             *
             * This is historical and is NOT reduced when money
             * is withdrawn.
             */
            $table->decimal('total_contributed', 15, 2)
                ->default(0);

            /*
             * Actual money currently held by the Campaign and
             * available for the creator to withdraw.
             */
            $table->decimal('withdrawable_amount', 15, 2)
                ->default(0);

            /*
             * Campaign withdrawal fee percentage.
             *
             * This is snapshotted when the Campaign is created.
             */
            $table->decimal('campaign_fee_percentage', 10, 4)
                ->default(0);

            /*
             * Campaign status:
             *
             * active
             * closed
             */
            $table->string('status', 20)
                ->default('active');

            /*
             * Useful lifecycle timestamps.
             */
            $table->timestamp('closed_at')
                ->nullable();

            $table->timestamp('last_withdrawn_at')
                ->nullable();

            $table->timestamps();

            /*
             * Useful indexes for Campaign queries.
             */
            $table->index([
                'company_id',
                'status',
            ]);

            $table->index([
                'user_id',
                'status',
            ]);

            $table->index([
                'company_id',
                'created_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};