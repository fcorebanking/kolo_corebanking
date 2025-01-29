<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDomAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dom_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('wb_account_detail_id');  // initiator user wb_account_details table           
            $table->foreign('wb_account_detail_id')->references('id')->on('wb_account_details'); 
           $table->integer('status')->default(0);
          
           $table->unsignedBigInteger('created_by')->nullable();  // initiator user  table           
           $table->foreign('created_by')->references('id')->on('users'); 
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
        Schema::dropIfExists('dom_accounts');
    }
}
