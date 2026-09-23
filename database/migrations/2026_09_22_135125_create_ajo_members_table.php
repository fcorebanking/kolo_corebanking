<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajo_members', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ajo_id')
                ->constrained('ajos')
                ->cascadeOnDelete();

            // The member's permanent personal Ajo Account is the source for scheduled collections
            // and the destination for maturity / early-leave returns.
            $table->foreignId('ajo_account_id')
                ->constrained('ajo_accounts')
                ->restrictOnDelete();

            // Legacy Finova user ID.
            $table->unsignedInteger('user_id');

            /*
             * This is the member's committed position inside THIS Ajo.
             * It is separate from the user's personal Ajo Account balance.
             *
             * A member's balance must never become negative.
             */
            $table->decimal('balance', 20, 2)->default(0);
            $table->decimal('total_contributed', 20, 2)->default(0);

            $table->unsignedInteger('insufficient_balance_count')->default(0);
            $table->decimal('total_insufficient_balance_fees', 20, 2)->default(0);
            $table->decimal('total_maturity_fees', 20, 2)->default(0);
            $table->decimal('total_early_leave_penalties', 20, 2)->default(0);

            $table->string('status', 30)->default('active');

            /*
             * Member accepted a snapshot of the creator's terms when joining.
             */
            $table->longText('terms_snapshot')->nullable();
            $table->string('terms_version', 30)->nullable();
            $table->dateTime('terms_accepted_at')->nullable();

            $table->dateTime('joined_at')->nullable();
            $table->dateTime('left_at')->nullable();

            /*
             * Early-leave approval workflow.
             */
            $table->dateTime('early_leave_requested_at')->nullable();
            $table->dateTime('early_leave_processed_at')->nullable();
            $table->unsignedInteger('early_leave_processed_by')->nullable();
            $table->text('early_leave_reason')->nullable();

            $table->timestamps();

            $table->unique(['ajo_id', 'user_id']);
            $table->index(['ajo_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('early_leave_processed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajo_members');
    }
};