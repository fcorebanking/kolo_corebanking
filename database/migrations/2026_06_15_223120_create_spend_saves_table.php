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
        Schema::create('spend_saves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // initiator users table
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->foreign('company_id')
                ->references('id')
                ->on('company')
                ->nullOnDelete();
            $table->double('spend_save_charge_or_percentage', 10, 2);
            $table->double('balance', 15, 4)->default(0);
            $table->unsignedBigInteger('status_id')->nullable()->default(7);
            $table->foreign('status_id')->references('id')->on('statuses');
            $table->timestamps();
            $table->index(['user_id', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spend_saves');
    }
};
