<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMandateLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mandate_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mandate_id');  //constrained to currencies type
            $table->foreign("mandate_id")->references("id")->on("mandates");
            $table->string('payment_reference');
            $table->string('marchant_ref');
            $table->string('response_description');
            $table->string('amount');
            $table->string('narration');
            $table->string('charge');
            $table->string('payment_status');
            $table->dateTime('payment_date');
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
        Schema::dropIfExists('mandate_logs');
    }
}
