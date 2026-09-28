<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Dropping the child table first to avoid foreign key constraint errors
        Schema::dropIfExists('fixed_savings_transactions');
        Schema::dropIfExists('fixed_savings');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the tables if rolled back (Optional: Leave empty if you don't want them back)
        Schema::create('fixed_savings', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('fixed_savings_transactions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }
};
