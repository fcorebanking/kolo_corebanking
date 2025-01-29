<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoinremittersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coinremitters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');  //constrained to currencies type
            $table->foreign("company_id")->references("id")->on("company");
            $table->double('naira_to_USD',5,2);
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
        Schema::dropIfExists('coinremitters');
    }
}
