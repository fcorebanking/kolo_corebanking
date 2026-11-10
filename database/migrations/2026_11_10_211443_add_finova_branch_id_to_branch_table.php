<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::table('branch', function (Blueprint $table) {
        $table->unsignedBigInteger('finova_branch_id')
            ->nullable()
            ->after('company_id');

        $table->unique(
            ['company_id', 'finova_branch_id'],
            'branch_company_finova_unique'
        );
    });
}

public function down(): void
{
    Schema::table('branch', function (Blueprint $table) {
        $table->dropUnique('branch_company_finova_unique');
        $table->dropColumn('finova_branch_id');
    });
}
};
