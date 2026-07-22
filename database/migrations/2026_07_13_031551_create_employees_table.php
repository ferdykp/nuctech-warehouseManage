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
            // $table->string('nik')->unique()->nullable(); // Nomor Induk Karyawan (Unik)
            $table->string('name');
            $table->string('phone_number');

            // --- 2. Informasi Kepegawaian (Core HR) ---
            $table->string('position')->nullable(); // Jabatan (e.g., Staf Admin, Supervisor)
            // $table->string('department')->nullable(); // Divisi (e.g., Operasional, HRD)
            $table->enum('status', ['Permanent', 'Contract', 'Probation', 'Daily'])
                ->default('Probation'); // Status Kepegawaian
            $table->date('join_date')->nullable(); // Tanggal Masuk Kerja
            $table->date('contract_start_date')->nullable(); // Tanggal Masuk Kerja
            // $table->date('contract_end_date')->nullable(); // Tanggal Selesai Kontrak (jika ada)

            // --- 3. Data Pribadi Legal & Kontak ---
            // $table->string('ktp_number', 16)->unique()->nullable(); // NIK KTP (16 Digit)
            // $table->string('place_of_birth')->nullable();
            // $table->date('date_of_birth')->nullable();
            // $table->enum('gender', ['L', 'P'])->nullable(); // L = Laki-laki, P = Perempuan
            // $table->string('phone_number', 20)->nullable(); // Nomor HP aktif
            // $table->string('email')->unique()->nullable(); // Email karyawan (kantor/pribadi)
            // $table->text('address_ktp')->nullable(); // Alamat sesuai KTP
            // $table->text('address_domicile')->nullable(); // Alamat tinggal saat ini

            // --- 4. Kontak Darurat (Emergency Contact) ---
            // $table->string('emergency_contact_name')->nullable();
            // $table->string('emergency_contact_relation')->nullable(); // Hubungan (e.g., Orang tua, Istri)
            // $table->string('emergency_contact_phone', 20)->nullable();

            // --- 5. Data Finansial, Pajak, & Jaminan Sosial (Payroll & Benefit) ---
            // $table->string('bank_name')->nullable(); // Nama Bank (e.g., BCA, Mandiri)
            // $table->string('bank_account_number')->nullable(); // Nomor Rekening
            // $table->string('bank_account_holder')->nullable(); // Nama di Buku Tabungan
            // $table->string('npwp_number', 20)->unique()->nullable(); // Nomor Pokok Wajib Pajak
            // $table->string('bpjs_ketenagakerjaan', 20)->nullable(); // Nomor KPJ BPJS TK
            // $table->string('bpjs_kesehatan', 20)->nullable(); // Nomor BPJS Kesehatan
            // $table->enum('ptkp_status', ['TK/0', 'TK/1', 'TK/2', 'TK/3', 'K/0', 'K/1', 'K/2', 'K/3'])
            //     ->default('TK/0'); // Status Penghasilan Tidak Kena Pajak (Pajak PPh 21)

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
