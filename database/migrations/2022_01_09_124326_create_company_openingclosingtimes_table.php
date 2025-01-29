<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyOpeningclosingtimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_openingclosingtimes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('company'); 
           
            $table->string('nday',1)->nullable();
            $table->date('holiday')->nullable();
            $table->time('starttime',1)->nullable();
            $table->time('endtime',1)->nullable();
            $table->unsignedBigInteger('status_id');  // derive from bank table
            $table->foreign('status_id')->references('id')->on('company_statuses'); 
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users'); 
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->foreign('modified_by')->references('id')->on('users'); 
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
        Schema::dropIfExists('company_openingclosingtimes');
    }
}
