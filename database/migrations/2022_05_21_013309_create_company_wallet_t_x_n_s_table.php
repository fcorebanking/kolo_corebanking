<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyWalletTXNSTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_wallet_t_x_n_s', function (Blueprint $table) {
            $table->id(); 
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->double('amount', 15, 8); 
            $table->string('type'); 
            
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
        Schema::dropIfExists('company_wallet_t_x_n_s');
    }
}
