<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerCreditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_credits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->foreign('company_id')->references('id')->on('company');   
            $table->text('name',199);
            $table->string('method',10);
            $table->text('url');
            $table->text('authorization');
            $table->text('body');
            $table->double('callCost',15,2);
            $table->integer('status')->default(1)->comment('1=active,2=disable');
            $table->integer('access')->default(1)->comment('1=general,2=company');
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
        Schema::dropIfExists('customer_credits');
    }
}
