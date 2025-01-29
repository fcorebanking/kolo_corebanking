<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTempusersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tempusers', function (Blueprint $table) {
            $table->id();
            $table->string('old_wba_user')->nullable();
            $table->string('acc_ref')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('dob')->nullable();
            $table->string('sex')->nullable();
            $table->string('username')->nullable();
            $table->string('address')->nullable();
            $table->double('loanBalance', 15, 2);
            $table->string('bvn')->nullable();
            $table->string('nin')->nullable();
            $table->string('ac_no')->nullable();
            $table->string('bank_code')->nullable();
            $table->string('reg_date')->nullable();
            $table->string('balance')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('status_id');
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
        Schema::dropIfExists('tempusers');
    }
}
