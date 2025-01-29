<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRemitaCredentialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('remita_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('apiKey');
            $table->string('machant',15);
            $table->string('apiToken');
            $table->unsignedBigInteger('ownership')->default(1)->comment('0=wba 1=company');//0=wba 1=company
            $table->unsignedBigInteger('iswba')->default(0);//0=wba 1=company
            $table->unsignedBigInteger('company_id');
            $table->foreign("company_id")->references("id")->on("company");
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
        Schema::dropIfExists('remita_credentials');
    }
}
