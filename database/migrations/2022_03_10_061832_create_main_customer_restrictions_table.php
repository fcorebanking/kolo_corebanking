<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMainCustomerRestrictionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('main_customer_restrictions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');   // initiator users table 
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('company_id');   // initiator users table 
            $table->foreign('company_id')->references('id')->on('company');
            $table->unsignedBigInteger('customer_permission_id');
            $table->foreign('customer_permission_id')->references('id')->on('customer_permissions');
            $table->string('remark');
            $table->unsignedBigInteger('created_by')->nullable();
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
        Schema::dropIfExists('main_customer_restrictions');
    }
}
