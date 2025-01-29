<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRemitaMandatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('remita_mandates', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('loan_id');
            $table->string('customerId');
            $table->string('authorisationCode');
            $table->string('mandateReference');
            $table->string('responseId');
            $table->string('status');
            $table->json('data');
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
        Schema::dropIfExists('remita_mandates');
    }
}
