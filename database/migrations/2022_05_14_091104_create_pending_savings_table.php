<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePendingSavingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pending_savings', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->string('amount')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->string('desc')->nullable();
            $table->string('account_type')->nullable();
            $table->string('pending')->nullable();
            $table->string('w_charge')->nullable();
            $table->string('created_by')->nullable();
            $table->unsignedBigInteger('status_id');
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
        Schema::dropIfExists('pending_savings');
    }
}
