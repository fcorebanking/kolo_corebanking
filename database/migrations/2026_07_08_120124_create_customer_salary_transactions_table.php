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
        Schema::create('customer_salary_transactions', function (Blueprint $table) {
            $table->id();
              $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('recipient_name');

            $table->string('recipient_account');

            $table->string('recipient_bank');

            $table->string('bank_code');

            $table->decimal('amount', 15, 2);

            $table->date('scheduled_date');

            $table->time('scheduled_time');

            // save the transaction PIN securely
            $table->string('transaction_pin');

            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled',
            ])->default('pending');

            $table->unsignedInteger('attempts')->default(0);

            $table->timestamp('executed_at')->nullable();

            $table->text('failure_reason')->nullable();

            $table->string('token');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_salary_transactions');
    }
};
