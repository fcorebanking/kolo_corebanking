<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHasAgentToEsusus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esusus', function (Blueprint $table) {
            $table->unsignedBigInteger('hasAgent')->default(0);//to know if esusu is an agent enable
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esusus', function (Blueprint $table) {
            //
        });
    }
}
