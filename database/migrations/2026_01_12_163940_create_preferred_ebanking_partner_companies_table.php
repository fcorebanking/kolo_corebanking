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

            $table->unsignedBigInteger('bank_id')->nullable();
            $table->unsignedBigInteger('bank_verification_id')->nullable();

            $table->foreign('bank_id')
                ->references('id')
                ->on('ebanking_partners')
                ->nullOnDelete();

            $table->foreign('bank_verification_id')
                ->references('id')
                ->on('ebanking_partners')
                ->nullOnDelete();

            $table->unsignedBigInteger('company_id')->nullable();
            $table->foreign('company_id')->references('id')->on('company')->nullOnDelete();

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
