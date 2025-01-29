<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOtherIncomesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('other_incomes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('other_income_type_id');
            $table->foreign('other_income_type_id')->references('id')->on('other_income_types');
            $table->double('amount',9,2);
            $table->date('income_date');
            $table->boolean('is_recurring')->default(0);
            $table->integer('recurring_income_period')->nullable();
            $table->text('description')->nullable();
            $table->text('invoice_receipt')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('modified_by');
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
        Schema::dropIfExists('other_incomes');
    }
}
