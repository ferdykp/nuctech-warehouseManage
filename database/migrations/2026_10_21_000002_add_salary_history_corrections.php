<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employee_salary_histories', fn (Blueprint $table) => $table->timestamp('cancelled_at')->nullable());
        Schema::create('salary_history_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_salary_history_id')->constrained()->cascadeOnDelete();
            $table->string('action', 30);
            $table->string('reason', 255);
            $table->string('actor');
            $table->json('before');
            $table->json('after');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('salary_history_audits');
        Schema::table('employee_salary_histories', fn (Blueprint $table) => $table->dropColumn('cancelled_at'));
    }
};
