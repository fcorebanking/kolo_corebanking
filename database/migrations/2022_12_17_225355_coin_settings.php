<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CoinSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coin_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');  //constrained to currencies type
            $table->foreign("company_id")->references("id")->on("company");
            $table->unsignedBigInteger('created_by');  //constrained to currencies type
            $table->foreign("created_by")->references("id")->on("users");
            $table->string('code');  //
            $table->text('api_key');  //
            $table->text('password');  //
            $table->integer('status_id');  //
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
}
