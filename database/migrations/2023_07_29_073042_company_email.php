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

        Schema::create('companyemail', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->uniqid();
            $table->string('MAIL_MAILER');
            $table->string('MAIL_HOST');
            $table->string('MAIL_PORT');
            $table->string('MAIL_USERNAME');
            $table->string('MAIL_PASSWORD');
            $table->string('MAIL_ENCRYPTION');
            $table->string('MAIL_FROM_ADDRESS');
            $table->string('MAIL_FROM_NAME');
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('company');
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
        //
    }
};
