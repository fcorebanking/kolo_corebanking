<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('preferred_ebanking_partner_companies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bank_transfer_id')
                ->nullable()
                ->constrained('ebanking_partners')
                ->nullOnDelete();

            $table->foreignId('bank_account_verification_id')
                ->nullable()
                ->constrained('ebanking_partners')
                ->nullOnDelete();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained('id')
                ->nullOnDelete();

            $table->index(['company_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('preferred_ebanking_partner_companies');
    }
};
