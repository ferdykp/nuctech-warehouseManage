<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Menambahkan 'Resigned' ke dalam definisi ENUM status
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('status', ['Permanent', 'Contract', 'Probation', 'Daily', 'Resigned'])->default('Probation')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('status', ['Permanent', 'Contract', 'Probation', 'Daily'])->default('Probation')->change();
        });
    }
};
