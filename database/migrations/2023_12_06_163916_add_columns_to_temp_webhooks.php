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
        Schema::table('temp_webhooks', function (Blueprint $table) {
            
            $table->double('charge', 12, 2)->nullable()->after('amount');
            $table->dateTime('transaction_date')->nullable()->after('amount');
            $table->string('description')->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('temp_webhooks', function (Blueprint $table) {
            //
        });
    }
};
