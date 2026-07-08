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
        Schema::create('customer_salaries', function (Blueprint $table) {
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

            $table->timestamps();
        });

        Schema::table('scheduled_payments', function (Blueprint $table) {
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_salaries');
    }
};
