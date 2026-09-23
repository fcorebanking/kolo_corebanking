<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajos', function (Blueprint $table) {
            $table->id();

            // The creator is the overseer of the Ajo, NOT an Ajo member.
            $table->unsignedInteger('creator_user_id');
            $table->unsignedInteger('company_id');

            // The creator's permanent personal Ajo Account receives creator fee shares.
            $table->foreignId('creator_ajo_account_id')
                ->constrained('ajo_accounts')
                ->restrictOnDelete();

            $table->string('ajo_name', 150);
            $table->text('description')->nullable();

            /*
             * Automatic scheduled collection settings.
             *
             * collection_day:
             * - weekly: weekday number chosen by the service/UI
             * - monthly: day of month
             * - daily: can remain null
             */
            $table->decimal('collection_amount', 20, 2)->default(0);
            $table->string('collection_frequency', 30)->default('monthly');
            $table->unsignedInteger('collection_interval')->default(1);
            $table->unsignedTinyInteger('collection_day')->nullable();
            $table->time('collection_time')->nullable();

            $table->date('start_date');
            $table->date('maturity_date');
            $table->dateTime('next_collection_at')->nullable();

            /*
             * Company-controlled fees are snapshotted onto the Ajo so future
             * company setting changes cannot alter an already-running Ajo.
             */
            $table->decimal('creation_fee', 20, 2)->default(0);
            $table->decimal('maturity_fee_percentage', 8, 4)->default(0);

            /*
             * Creator-controlled member-leaving penalty.
             * Supported types: fixed | percentage
             */
            $table->string('early_leave_penalty_type', 20)->default('percentage');
            $table->decimal('early_leave_penalty_value', 20, 4)->default(0);

            /*
             * Creator-controlled insufficient-balance fee.
             * Supported types: fixed | percentage
             */
            $table->string('insufficient_balance_fee_type', 20)->default('fixed');
            $table->decimal('insufficient_balance_fee_value', 20, 4)->default(0);

            /*
             * Fixed 40/60 fee split, snapshotted per Ajo.
             * Company = 40%, Creator = 60%.
             *
             * These values must also be printed into the Ajo conditions.
             */
            $table->decimal('company_fee_share_percentage', 5, 2)->default(40.00);
            $table->decimal('creator_fee_share_percentage', 5, 2)->default(60.00);

            $table->unsignedInteger('max_members')->nullable();
            $table->string('visibility', 20)->default('public');

            /*
             * The exact conditions accepted by members are preserved here.
             */
            $table->longText('terms')->nullable();
            $table->string('terms_version', 30)->default('1.0');

            $table->string('status', 30)->default('draft');

            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['creator_user_id', 'status']);
            $table->index('next_collection_at');
            $table->index(['visibility', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajos');
    }
};