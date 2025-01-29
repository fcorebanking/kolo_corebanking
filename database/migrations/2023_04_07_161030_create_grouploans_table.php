<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrouploansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grouploans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('leader')->nullable();
            $table->foreign('leader')->references('id')->on('users');
            $table->unsignedBigInteger('secretary')->nullable();
            $table->foreign('secretary')->references('id')->on('users');
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->foreign('manager_id')->references('id')->on('users');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->foreign('company_id')->references('id')->on('company');
            $table->integer('max_member')->default(0);
            $table->double('max_principal',15,2)->default(0);
            $table->double('max_interest',5,2)->default(0);
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
        Schema::dropIfExists('grouploans');
    }
}
