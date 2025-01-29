<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMandatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mandates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();  //constrained to currencies type         
            $table->foreign("user_id")->references("id")->on("users");
            $table->string('payer_name');
            $table->string('pay_address');
            $table->string('email_address');
            $table->string('phone_number');
            $table->unsignedBigInteger('bank_id')->nullable()->default(7);
            $table->foreign('bank_id')->references('id')->on('banks'); // user derive from status table
            $table->string('account_number');
            $table->string('account_name');
            $table->string('narration');
            $table->string('variable_amounts');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('first_payment_date');
            $table->string('subscriber_code');
            $table->string('repeat');
            $table->string('frequency');
            $table->double('amount', 15, 4)->default(0); // 
            $table->string('mandate_code')->unique('mandate_code'); // 
            $table->unsignedBigInteger('status_id')->nullable()->default(7);
            $table->foreign('status_id')->references('id')->on('statuses'); // user derive from status table
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
        Schema::dropIfExists('mandates');
    }
}
