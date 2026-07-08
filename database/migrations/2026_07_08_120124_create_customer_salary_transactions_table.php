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

            $table->foreignId('customer_salary_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('transaction_id')
                ->constrained()
                ->cascadeOnDelete();

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
