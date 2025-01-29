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
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->string('beneficiary_iban')->nullable();
            $table->string('beneficiary_account_number')->nullable();
            $table->string('beneficiary_sort_code')->nullable();
            $table->string('beneficiary_name')->nullable();
            $table->string('beneficiary_country')->nullable();
            $table->string('beneficiary_bank_name')->nullable();
            $table->string('beneficiary_bank_address')->nullable();
            $table->string('beneficiary_swift_code')->nullable();
            $table->string('beneficiary_email')->nullable();
            $table->string('beneficiary_phone')->nullable();
            $table->string('beneficiary_address')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
