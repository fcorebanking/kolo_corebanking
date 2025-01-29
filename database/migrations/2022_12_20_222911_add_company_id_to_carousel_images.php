<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanyIdToCarouselImages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('carousel_images', function (Blueprint $table) {
            // $table->unsignedBigInteger('company_id');
            $table->text('image_path')->nullable();

            // $table->foreign("company_id")->references("id")->on("company");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('carousel_images', function (Blueprint $table) {
            //
        });
    }
}
