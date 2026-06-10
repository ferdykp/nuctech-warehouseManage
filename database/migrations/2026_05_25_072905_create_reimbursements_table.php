<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reimbursements', function (Blueprint $table) {
            $table->id();
            // Menggunakan tabel 'users' sesuai dengan struktur autentikasi proyek Anda
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Parameter Baru
            $table->string('person_name'); // Nama orang yang mengajukan/menggunakan dana
            $table->date('date'); // Tanggal pengeluaran
            $table->enum('category', ['transportation', 'delivery', 'office']); // Kategori Enum

            // Kolom Kondisional (Nullable karena kategori 'office' tidak memerlukannya)
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();

            $table->decimal('amount', 12, 2); // Nominal Uang
            $table->text('comment')->nullable(); // Kolom Komen/Keterangan
            $table->string('receipt_attachment'); // Bukti Invoice/Nota
            // $table->string('digital_signature')->nullable();

            // Sistem Approval
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('rejected_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reimbursements');
    }
};
