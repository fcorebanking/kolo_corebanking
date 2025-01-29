<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');  // initiator users table           
            $table->foreign('user_id')->nullable()->references('id')->on('users');
            $table->string('subject');
            $table->text('message');
            $table->integer('num_page');
            $table->decimal('amount',5,2);
            $table->integer('status')->default(0)->comment('1=seen,0=unseen');
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
        Schema::dropIfExists('notifications');
    }
}
