<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddToAppLoanSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('apploan_settings', function (Blueprint $table) {
            $table->integer('NumOngoinAllowed')->default(1);  
            $table->text('remark')->nullable();  
            
        });
       
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('app_loan_settings', function (Blueprint $table) {
            //
        });
    }
}
