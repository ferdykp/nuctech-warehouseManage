<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. MASTER JENIS CUTI & IZIN
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., Cuti Tahunan, Cuti Sakit, Izin Khusus, Unpaid Leave
            $table->integer('default_quota')->default(12); // Quota standar setahun
            $table->boolean('is_paid')->default(true); // true = Dibayar, false = Potong Gaji
            $table->boolean('cut_annual_quota')->default(true); // Memotong kuota tahunan atau tidak
            $table->boolean('requires_file')->default(false); // Membutuhkan attachment (e.g. Surat Dokter)
            $table->timestamps();
        });

        // 2. SALDO CUTI KARYAWAN PER TAHUN (Quota Tracking)
        Schema::create('employee_leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('cascade');
            $table->year('year'); // e.g. 2026
            $table->integer('total_quota')->default(12);
            $table->integer('used_quota')->default(0);
            $table->integer('remaining_quota')->default(12);
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'year']); // 1 karyawan hanya punya 1 record per jenis cuti per tahun
        });

        // 3. FORM PENGAJUAN CUTI & WORKFLOW APPROVAL
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_days'); // Jumlah hari kerja efektif yang diambil
            $table->text('reason');
            $table->string('attachment_file')->nullable(); // Surat dokter/bukti

            // Approval Workflow Status
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); // Admin Site / HR yang approve
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });

        // 4. PENGAJUAN TUKAR SHIFT / LEMBUR PENGGANTI (Overtime Cover)
        Schema::create('shift_replacement_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->nullable()->constrained('leave_requests')->onDelete('cascade'); // Link ke cuti (jika karena cuti)
            $table->foreignId('original_employee_id')->constrained('employees')->onDelete('cascade'); // Karyawan B (Yang Cuti)
            $table->foreignId('replacement_employee_id')->constrained('employees')->onDelete('cascade'); // Karyawan A (Pengganti)
            $table->date('date');
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->onDelete('cascade'); // Shift yang digantikan
            $table->text('reason')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['original_employee_id', 'replacement_employee_id', 'date'], 'shift_rep_emp_date_idx');
        });

        // 5. UPDATE TABEL EMPLOYEE SCHEDULES (Tambahan Kolom Penanda Overtime Cover)
        if (Schema::hasTable('employee_schedules')) {
            Schema::table('employee_schedules', function (Blueprint $table) {
                $table->boolean('is_overtime_cover')->default(false)->after('shift_id');
                $table->foreignId('covered_employee_id')->nullable()->constrained('employees')->onDelete('set null')->after('is_overtime_cover');
                $table->foreignId('shift_replacement_request_id')->nullable()->constrained('shift_replacement_requests')->onDelete('set null')->after('covered_employee_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employee_schedules')) {
            Schema::table('employee_schedules', function (Blueprint $table) {
                $table->dropForeign(['covered_employee_id']);
                $table->dropForeign(['shift_replacement_request_id']);
                $table->dropColumn(['is_overtime_cover', 'covered_employee_id', 'shift_replacement_request_id']);
            });
        }

        Schema::dropIfExists('shift_replacement_requests');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('employee_leave_balances');
        Schema::dropIfExists('leave_types');
    }
};
