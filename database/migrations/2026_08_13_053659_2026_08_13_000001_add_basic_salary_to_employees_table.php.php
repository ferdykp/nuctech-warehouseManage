<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('email')->unique()->nullable()->after('name');
            $table->string('nik', 16)->unique()->nullable()->after('email');
            $table->decimal('basic_salary', 15, 2)->default(0)->after('status');
            $table->string('bank_name', 100)->nullable()->after('basic_salary');
            $table->string('bank_account_number', 100)->nullable()->after('bank_name');
            $table->enum('mcu', ['yes', 'no'])->after('bank_account_number');
            $table->enum('tld', ['yes', 'no'])->after('mcu');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('basic_salary');
        });
    }
};
