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
        Schema::create('closings_balances', function (Blueprint $table) {
            $table->id();
            $table->string('currency')->default('NGN');
            $table->string('status')->default('open');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->foreign('company_id')->references('id')->on('company');
            $table->unsignedBigInteger('ten_thousand_quantity')->default(0);
            $table->double('ten_thousand_amount', 12, 2);
            $table->unsignedBigInteger('five_thousand_quantity')->default(0);
            $table->double('five_thousand_amount', 12, 2);
            $table->unsignedBigInteger('two_thousand_quantity')->default(0);
            $table->double('two_thousand_amount', 12, 2);
            $table->unsignedBigInteger('one_thousand_quantity')->default(0);
            $table->double('one_thousand_amount', 12, 2);
            $table->unsignedBigInteger('five_hundred_note_quantity')->default(0);
            $table->double('five_hundred_note_amount', 12, 2);
            $table->unsignedBigInteger('five_hundred_coin_quantity')->default(0);
            $table->double('five_hundred_coin_amount', 12, 2);
            $table->unsignedBigInteger('one_hundred_quantity')->default(0);
            $table->double('one_hundred_amount', 12, 2);
            $table->unsignedBigInteger('fifthy_quantity')->default(0);
            $table->double('fifthy_amount', 12, 2);
            $table->unsignedBigInteger('twenty_five_quantity')->default(0);
            $table->double('twenty_five_amount', 12, 2);
            $table->unsignedBigInteger('ten_quantity')->default(0);
            $table->double('ten_amount', 12, 2);
            $table->unsignedBigInteger('five_quantity')->default(0);
            $table->double('five_amount', 12, 2);
            $table->unsignedBigInteger('two_quantity')->default(0);
            $table->double('two_amount', 12, 2);
            $table->unsignedBigInteger('one_quantity')->default(0);
            $table->double('one_amount', 12, 2);
            $table->double('total', 12, 2);
            $table->date('input_date');
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
        //
    }
};
