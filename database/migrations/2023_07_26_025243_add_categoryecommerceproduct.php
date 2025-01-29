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
        Schema::table('ecomerce_product_models', function (Blueprint $table) {
            $table->unsignedBigInteger('ecommerce_categorie_id')->nullable();
            $table->foreign('ecommerce_categorie_id')->references('id')->on('ecommerce_categories');
        });

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
};
