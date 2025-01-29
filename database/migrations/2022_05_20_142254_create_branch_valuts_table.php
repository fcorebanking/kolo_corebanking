<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBranchValutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('branch_valuts', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('company_valut_id');
            $table->double('value', 15, 8); 
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->integer('status')->default(1);
            
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
        Schema::dropIfExists('branch_valuts');
    }
}
