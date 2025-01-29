<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\Type;
class AddToCoinremitterTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('coinremitter_transactions', function (Blueprint $table) {
            if (!Type::hasType('double')) {
                Type::addType('double', FloatType::class);
            }
            $table->string('type');
            $table->string('confirmations');
            $table->double('processing_fees',9,9)->default(0)->change();
            $table->double('transaction_fees',9,9)->default(0)->change();
            $table->string('with_id')->nullable()->change();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('coinremitter_transactions', function (Blueprint $table) {
            //
        });
    }
}
