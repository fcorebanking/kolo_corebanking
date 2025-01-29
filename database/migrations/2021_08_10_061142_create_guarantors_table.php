<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGuarantorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('guarantors', function (Blueprint $table) {
            $table->id(); 
            $table->unsignedBigInteger('user_id');  // derive from Users table
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->unsignedBigInteger('status_id');
            $table->double('salary', 9, 2)->nullable(); 
            $table->string('other_remarks')->nullable();
            $table->text('passport')->nullable();
            $table->string('bvn')->nullable();
            $table->string('relationship')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('occupation')->nullable();
            $table->text('signature')->nullable();
            
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
        Schema::dropIfExists('guarantors');
    }
}
