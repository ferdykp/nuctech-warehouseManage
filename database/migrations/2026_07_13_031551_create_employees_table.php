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
        Schema::create('employees', function (Blueprint $table) {
            // --- 1. Identitas Utama & Relasi ---
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('phone_number');

            // --- 2. Informasi Kepegawaian (Core HR) ---
            $table->string('position')->nullable(); // Jabatan (e.g., Staf Admin, Supervisor)
            $table->enum('status', ['Permanent', 'Contract', 'Probation', 'Daily'])
                ->default('Probation'); // Status Kepegawaian
            $table->date('join_date')->nullable(); // Tanggal Masuk Kerja
            $table->date('contract_start_date')->nullable(); // Tanggal Masuk Kerja

            // --- 6. Status Keaktifan Sistem ---
            $table->boolean('is_active')->default(true); // Status karyawan aktif/nonaktif di perusahaan
            $table->timestamps();

            // --- Indeks untuk Kecepatan Query (Optimasi Database) ---
            // $table->index('nik');
            $table->index('status');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
