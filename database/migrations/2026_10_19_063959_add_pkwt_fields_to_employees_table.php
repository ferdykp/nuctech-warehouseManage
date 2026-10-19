<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('pkwt_date')
                ->nullable()
                ->after('contract_start_date');

            $table->string('pkwt_number', 150)
                ->nullable()
                ->after('pkwt_date');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'pkwt_date',
                'pkwt_number',
            ]);
        });
    }
};
