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
        // Menggunakan DB::statement karena mengubah opsi ENUM di MySQL 
        // jauh lebih aman dan akurat menggunakan query mentah langsung.
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->enum('status', [
            'pending', 
            'pending_leader', 
            'pending_station', 
            'pending_manager', 
            'approved', 
            'rejected'
        ])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke struktur awal jika dilakukan rollback
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->enum('status', [
            'pending', 
            'approved', 
            'rejected'
        ])->default('pending')->change();
        });
    }
};
