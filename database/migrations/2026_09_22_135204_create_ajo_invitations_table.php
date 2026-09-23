<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajo_invitations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ajo_id')
                ->constrained('ajos')
                ->cascadeOnDelete();

            // The creator should be the one sending invitations.
            $table->unsignedInteger('invited_by');
            $table->unsignedInteger('invited_user_id');

            $table->string('status', 30)->default('pending');

            /*
             * Preserve exactly what the invited user was asked to accept.
             */
            $table->longText('terms_snapshot')->nullable();
            $table->string('terms_version', 30)->nullable();

            $table->dateTime('sent_at')->nullable();
            $table->dateTime('responded_at')->nullable();

            $table->timestamps();

            $table->index(['ajo_id', 'status']);
            $table->index(['invited_user_id', 'status']);
            $table->index('invited_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajo_invitations');
    }
};