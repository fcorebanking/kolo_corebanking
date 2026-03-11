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

            $table->unsignedBigInteger('bank_transfer_id')->nullable();
            $table->unsignedBigInteger('bank_account_verification_id')->nullable();

            $table->foreign('bank_transfer_id', 'pepc_bank_transfer_fk')
                ->references('id')
                ->on('ebanking_partners')
                ->nullOnDelete();

            $table->foreign('bank_account_verification_id', 'pepc_bank_verify_fk')
                ->references('id')
                ->on('ebanking_partners')
                ->nullOnDelete();

            $table->unsignedBigInteger('company_id')->nullable();

            $table->foreign('company_id', 'pepc_company_fk')
                ->references('id')
                ->on('company')
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
