<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('card_name', 40);
            $table->unsignedBigInteger('card_category_id');
            $table->foreign('card_category_id')->references('id')->on('card_categories');
            $table->unsignedBigInteger('card_type_id');
            $table->foreign('card_type_id')->references('id')->on('card_types');
            $table->string('card_number', 19);
            $table->string('masked_pan', 19);
            $table->string('card_reference', 50)->nullable();
            $table->unsignedBigInteger('currency_id');
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->string('exp_date', 7);
            $table->string('cvv', 3);
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
        Schema::dropIfExists('user_cards');
    }
}
