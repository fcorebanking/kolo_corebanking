<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePendingEsususTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pending_esusus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('esusuid'); // derive from esusu table
            $table->foreign('esusuid')->references('id')->on('esusus'); 
          
            $table->double('amount',9,2)->nullable();           
            $table->integer('number')->nullable();
            $table->string('wallet_to_main')->nullable();
            $table->string('withdraw_to_wallet')->nullable();
            $table->string('reset_rate')->nullable();
            $table->string('reset_date')->nullable();
            $table->integer('status_id')->default(1);
            $table->string('pending')->default(1);
            $table->integer('created_by');
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
        Schema::dropIfExists('pending_esusus');
    }
}
