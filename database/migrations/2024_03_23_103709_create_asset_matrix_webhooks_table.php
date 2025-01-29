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
        Schema::create('asset_matrix_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string("amount")->nullable();
            $table->string("account_name")->nullable();
            $table->string("narration")->nullable();
            $table->string("session_id")->nullable();
            $table->string("account_number")->nullable();
            $table->string("source_account_number")->nullable();
            $table->string("source_account_name")->nullable();
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
        Schema::dropIfExists('asset_matrix_webhooks');
    }
};
