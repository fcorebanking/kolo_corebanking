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
        Schema::create('pending_customer_saving_interests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wb_account_id');
            $table->foreign('wb_account_id')->references('id')->on('wb_account_details');
            $table->decimal('amount',15,2)->default(0);
            $table->string('status')->default('pending');
            $table->date('mark_date');
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
        Schema::dropIfExists('pending_customer_saving_interests');
    }
};
