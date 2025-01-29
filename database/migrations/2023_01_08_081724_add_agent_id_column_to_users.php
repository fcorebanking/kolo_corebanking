<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAgentIdColumnToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->foreign("agent_id")->references("id")->on("users");
            $table->integer('is_agent')->default(0);
        });
    }

    /**
     * Reverse the migrations. /Users/mac/Documents/laravel project/wiredbanking/database/migrations/2023_01_08_081724_add_agent_id_column_to_users.php
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
}
