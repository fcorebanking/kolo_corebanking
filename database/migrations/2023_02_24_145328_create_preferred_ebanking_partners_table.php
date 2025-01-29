<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreferredEbankingPartnersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('preferred_ebanking_partners', function (Blueprint $table) {
            $table->id();
            $table->string('ebanking_service');
            $table->unsignedBigInteger('ebanking_partners_id');
            $table->foreign('ebanking_partners_id')->references('id')->on('ebanking_partners');
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
        Schema::dropIfExists('preferred_ebanking_partners');
    }
}
