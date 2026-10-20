<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_allowances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('amount', 15, 2);
            $table->char('start_month', 7);
            $table->char('end_month', 7)->nullable();
            $table->string('notes', 500)->nullable();
            $table->string('certificate_path')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'start_month']);
        });
        Schema::table('salaries', fn (Blueprint $table) => $table->json('allowance_snapshot')->nullable());
    }

    public function down(): void
    {
        Schema::table('salaries', fn (Blueprint $table) => $table->dropColumn('allowance_snapshot'));
        Schema::dropIfExists('employee_allowances');
    }
};
