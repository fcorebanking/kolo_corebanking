<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddtransactionTypeToPendingSaving extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pending_savings', function (Blueprint $table) {
            $table->unsignedBigInteger('transaction_type_id');  //constrained to currencies type
            // $table->foreign("transaction_type_id")->references("id")->on("transaction_types");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pending_saving', function (Blueprint $table) {
            //
        });
    }
}
