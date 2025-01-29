<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InvestmentProductUpdate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('investment_products', function (Blueprint $table) {
           // $table->foreignId('frequency_id')->constrained()->change();
        });
        //DB::statement('ALTER TABLE investment_products ADD CONSTRAINT frequency_id CHECK ("start_date" < "end_date")');

        // Schema::table('investment_products', function (Blueprint $table) {
            // $table->unsignedBigInteger('frequency_id')->nullable()->change();  // derive from frequencies table
            // $table->foreign('frequency_id')->references('id')->on('frequencies')->change();
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
