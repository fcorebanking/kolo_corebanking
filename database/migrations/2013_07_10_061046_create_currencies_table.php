<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->string('symbol', 20);
            $table->integer('charge_by')->comment('0 - Charge by Percentage, 1 - charge by amount');
            $table->float('charge_percentage')->nullable();
            $table->float('capped_at')->nullable();
            $table->float('charge_amount')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();  //constrained to currencies type         
            $table->foreign("country_id")->references("id")->on("contries");
            $table->string('mime_url')->nullable();
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
        Schema::dropIfExists('currencies');
    }
}
