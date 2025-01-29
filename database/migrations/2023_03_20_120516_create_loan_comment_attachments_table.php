<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanCommentAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loan_comment_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_comment_id');
            $table->foreign('loan_comment_id')->references('id')->on('loan_comments');
            $table->text('attachment');
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
        Schema::dropIfExists('loan_comment_attachments');
    }
}
