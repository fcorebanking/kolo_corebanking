<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountLienLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_lien_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id')->nullable();  //constrained to currencies type         
            $table->foreign("wallet_id")->references("id")->on("user_wallets");
            $table->double('amount', 15, 4)->default(0); // 
            $table->string('lien_reference'); // 
            $table->string('remark'); // 
            $table->integer('lien_type')->comment("1 - Lien, 2 - Unlien"); // 
            $table->unsignedBigInteger('status_id')->nullable();
            $table->foreign('status_id')->references('id')->on('statuses'); // user derive from status table
            $table->unsignedBigInteger("created_by")->nullable();
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
        Schema::dropIfExists('account_lien_logs');
    }
}
