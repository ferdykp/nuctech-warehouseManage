<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_templates', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('version')->unique();
            $t->string('path');
            $t->string('sha256', 64);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('employee_contracts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained()->restrictOnDelete();
            $t->string('contract_number')->nullable()->unique();
            $t->date('contract_date')->nullable();
            $t->date('start_date')->nullable();
            $t->date('end_date')->nullable();
            $t->string('status')->default('draft')->index();
            $t->foreignId('template_id')->nullable()->constrained('contract_templates')->restrictOnDelete();
            $t->foreignId('previous_contract_id')->nullable()->constrained('employee_contracts')->restrictOnDelete();
            $t->json('snapshot')->nullable();
            $t->string('docx_path')->nullable();
            $t->string('pdf_path')->nullable();
            $t->string('signed_document_path')->nullable();
            $t->string('document_fingerprint', 64)->nullable();
            $t->date('notified_at')->nullable();
            $t->date('responded_at')->nullable();
            $t->string('employee_response')->nullable();
            $t->timestamp('activated_at')->nullable();
            $t->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['employee_id', 'start_date', 'end_date']);
            $t->index(['status', 'end_date']);
        });
        Schema::create('contract_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_contract_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action');
            $t->json('details')->nullable();
            $t->timestamps();
        });
        // Existing dates do not prove a contract's expiry: preserve them for verification only.
        DB::table('employees')->where(function ($q) {
            $q->whereNotNull('pkwt_date')->orWhereNotNull('pkwt_number');
        })
            ->orderBy('id')->chunkById(200, function ($employees) {
                foreach ($employees as $employee) {
                    DB::table('employee_contracts')->insert([
                        'employee_id' => $employee->id, 'contract_date' => $employee->pkwt_date,
                        'status' => 'needs_verification', 'snapshot' => json_encode(['legacy_contract_number' => $employee->pkwt_number]),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_events');
        Schema::dropIfExists('employee_contracts');
        Schema::dropIfExists('contract_templates');
    }
};
