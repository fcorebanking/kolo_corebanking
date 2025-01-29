<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerRestrictionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_restriction_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');   // initiator users table 
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('customer_permission_id');
            $table->foreign('customer_permission_id')->references('id')->on('customer_permissions');
            $table->string('remark');
            $table->integer('restricted_by_system')->nullable();
            $table->unsignedBigInteger('restriction_log_type_id')->nullable();
            $table->foreign('restriction_log_type_id')->references('id')->on('restriction_log_types');
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
        Schema::dropIfExists('customer_restriction_logs');
    }
}
