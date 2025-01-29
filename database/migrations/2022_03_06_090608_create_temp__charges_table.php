<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTempChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('temp__charges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wb_account_detail_id');  // initiator user wb_account_details table           
            $table->foreign('wb_account_detail_id')->references('id')->on('wb_account_details'); 
            $table->double('amount', 9, 2);
            $table->string('type');
            $table->date('date_due');
            $table->integer('status')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
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
        Schema::dropIfExists('temp__charges');
    }
}
