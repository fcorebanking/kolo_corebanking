<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('target_savings_schedules', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('target_saving_id')->unique();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('source_account_id')->index();

            $table->decimal('amount', 15, 2);

            $table->enum('frequency', [
                'daily',
                'weekly',
                'monthly',
            ])->index();

            $table->dateTime('first_deposit_at');
            $table->dateTime('next_deposit_at')->nullable()->index();
            $table->dateTime('last_deposit_at')->nullable();
            $table->dateTime('last_attempt_at')->nullable();

            $table->unsignedSmallInteger('notification_minutes_before')
                ->default(30);
            $table->dateTime('notification_sent_at')->nullable();

            $table->string('last_deposit_status', 50)
                ->default('pending');
            $table->text('last_deposit_error')->nullable();

            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();

            $table->foreign('target_saving_id')
                ->references('id')
                ->on('target_savings')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('target_savings_schedules');
    }
};
