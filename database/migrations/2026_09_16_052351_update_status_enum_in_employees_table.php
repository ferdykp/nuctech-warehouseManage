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
        DB::statement("ALTER TABLE employees MODIFY COLUMN status ENUM('Permanent', 'Contract', 'Probation', 'Daily', 'Resigned') NOT NULL DEFAULT 'Probation'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE employees MODIFY COLUMN status ENUM('Permanent', 'Contract', 'Probation', 'Daily') NOT NULL DEFAULT 'Probation'");
    }
};
