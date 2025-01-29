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
        Schema::create('savings_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->string('invoice_code');
            $table->decimal('amount',9,0);
            $table->string('period_start');
            $table->string('period_end');
            $table->boolean('paid');
            $table->string('paid_at');
            $table->string('description');
            $table->string('transaction_reference')->nullable();
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
        Schema::dropIfExists('savings_invoices');
    }
};
